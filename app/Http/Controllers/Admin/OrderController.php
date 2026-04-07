<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'provider'])->latest()->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'provider'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    /**
     * Join Requests (Providers & Sellers)
     */
    public function joinRequests()
    {
        $status = request('status', \App\Models\User::STATUS_PENDING);
        
        $requests = \App\Models\User::where('approval_status', $status)
            ->where(function($q) {
                $q->has('serviceProvider')->orHas('seller');
            })
            ->with(['serviceProvider.mainCategory', 'seller', 'location'])
            ->latest()
            ->paginate(20);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => true,
                'requests' => $requests,
            ]);
        }

        return view('admin.users.join_requests', compact('requests', 'status'));
    }

    public function approve(Request $request, $id = null)
    {
        $id = $id ?? $request->request_id;
        $user = \App\Models\User::findOrFail($id);
        
        // Determine correct role based on pending record
        $newRole = \App\Models\User::TYPE_CUSTOMER;
        if ($user->serviceProvider) {
            $newRole = \App\Models\User::TYPE_PROVIDER;
        } elseif ($user->seller) {
            $newRole = \App\Models\User::TYPE_SELLER;
        }

        $user->update([
            'user_type' => $newRole,
            'approval_status' => \App\Models\User::STATUS_APPROVED,
            'is_active' => true
        ]);

        // إرسال إشعار للمستخدم
        $title = "تم قبول طلب انضمامك";
        $roleLabel = ($user->user_type == \App\Models\User::TYPE_PROVIDER) ? "مقدم خدمة" : "تاجر";
        $type = ($user->user_type == \App\Models\User::TYPE_PROVIDER) ? "provider_approved" : "store_approved";
        $message = "تهانينا! تم قبول طلبك للعمل كـ $roleLabel في التطبيق. يمكنك الآن البدء.";

        \App\Models\Notification::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'target_role' => 'customer',
            'notifiable_type' => $type,
        ]);

        if ($user->fcm_token) {
            $fcm = new \App\Services\FcmService();
            $fcm->sendNotification($user->fcm_token, $title, $message, [
                'type' => $type,
                'status' => 'approved'
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'تم قبول الطلب بنجاح']);
        }

        return redirect()->back()->with('success', 'تم قبول الطلب وتفعيل الحساب بنجاح.');
    }

    public function reject(Request $request, $id = null)
    {
        $id = $id ?? $request->request_id;
        $user = \App\Models\User::with(['serviceProvider', 'seller'])->findOrFail($id);

        // Determine role
        $isProvider = (bool) $user->serviceProvider;
        $type = $isProvider ? "provider_rejected" : "store_rejected";

        // IMPORTANT: We no longer delete serviceProvider or seller records 
        // to allow the admin to see the rejected requests in history.

        $user->update([
            'user_type' => \App\Models\User::TYPE_CUSTOMER,
            'approval_status' => \App\Models\User::STATUS_REJECTED,
            'is_active' => true,
            'rejection_reason' => $request->reason
        ]);

        // إرسال إشعار للمستخدم
        $title = "بناءً على طلب الانضمام";
        $message = "للأسف، تم رفض طلبك للانضمام. السبب: " . ($request->reason ?? "غير محدد");

        \App\Models\Notification::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'target_role' => 'customer',
            'notifiable_type' => $type,
        ]);

        if ($user->fcm_token) {
            $fcm = new \App\Services\FcmService();
            $fcm->sendNotification($user->fcm_token, $title, $message, [
                'type' => $type,
                'status' => 'rejected',
                'reason' => $request->reason
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'تم رفض الطلب']);
        }

        return redirect()->back()->with('success', 'تم رفض الطلب.');
    }
}
