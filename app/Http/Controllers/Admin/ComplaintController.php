<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
    /**
     * Display a listing of complaints (Admin).
     */
    public function index()
    {
        $complaints = Complaint::with(['user'])->latest()->paginate(10);
        return view('admin.orders.complaints', compact('complaints'));
    }

    /**
     * Store a new complaint (API).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'type' => 'required|in:complaint,suggestion',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'order_id' => 'nullable|exists:orders,order_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $complaint = Complaint::create([
            'user_id' => $request->user_id,
            'order_id' => $request->order_id,
            'type' => $request->type,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم استلام طلبك بنجاح، شكراً لتواصلك معنا.',
            'data' => $complaint
        ], 201);
    }

    /**
     * Update complaint status and reply (Admin).
     */
    public function updateStatus(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        
        $complaint->update([
            'status' => $request->status,
            'admin_reply' => $request->admin_reply,
        ]);

        // Send Push Notification if there is a reply or status change
        $user = $complaint->user;
        if ($user && $user->fcm_token) {
            $fcmService = new \App\Services\FcmService();
            $title = $complaint->type == 'suggestion' ? 'تحديث بخصوص اقتراحك' : 'تحديث بخصوص شكواك';
            $body = "تم تحديث حالة طلبك إلى: " . $this->getStatusArabic($request->status);
            
            if ($request->admin_reply) {
                $body .= "\nرد الإدارة: " . $request->admin_reply;
            }

            $fcmService->sendNotification($user->fcm_token, $title, $body, [
                'type' => 'complaint_update',
                'complaint_id' => $complaint->id
            ]);

            // Also save to database notifications table if exists
            \App\Models\Notification::create([
                'user_id' => $user->user_id,
                'title' => $title,
                'message' => $body,
                'type' => 'complaint',
                'is_read' => false,
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث حالة الشكوى وإرسال الرد للمستخدم.');
    }

    /**
     * Get complaints for the authenticated user (API).
     */
    public function getUserComplaints(Request $request)
    {
        $complaints = Complaint::where('user_id', $request->user()->user_id)
            ->latest()
            ->get();
            
        return response()->json([
            'status' => true,
            'data' => $complaints
        ]);
    }

    private function getStatusArabic($status) {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'processing' => 'جاري المعالجة',
            'resolved' => 'تم الحل',
            'ignored' => 'تم التجاهل',
        ];
        return $statuses[$status] ?? $status;
    }
}
