<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * جلب قائمة الإشعارات الخاصة بالمستخدم الحالي.
     * (هذه الدالة هي التي يتم مناداتها عند فتح شاشة "الإشعارات" في التطبيق)
     */
    public function index(Request $request)
    {
        // 1. تحديد المستخدم الذي أرسل الطلب عن طريق التوكن (Token)
        $user = $request->user();
        
        // 2. الاستعلام من قاعدة البيانات لجلب إشعارات هذا المستخدم فقط
        $notifications = Notification::where('user_id', $user->user_id)
            // إذا تم إرسال 'role' في الطلب، قم بفلترة الإشعارات بناءً على دور المستخدم المستهدف (تاجر، عميل، إلخ)
            ->when($request->role, function($query, $role) {
                return $query->where('target_role', $role);
            })
            // ترتيب الإشعارات من الأحدث إلى الأقدم بناءً على تاريخ الإنشاء
            ->latest()
            ->get();
            
        // 3. إعادة تهيئة البيانات (Mapping) لتظهر بشكل مناسب ومنسق في تطبيق Flutter
        $data = $notifications->map(function($n) {
            return [
                'id' => (string) $n->id,
                'title' => $n->title, // عنوان الإشعار
                'message' => $n->message, // تفاصيل الإشعار
                'isRead' => (bool) $n->is_read, // هل تم قرائته أم لا (لتغيير اللون في التطبيق مثلاً)
                
                // تحديد نوع الإشعار لمعرفة أين يوجه التطبيق المستخدم عند النقر عليه (الطلب، الصيانة، الانضمام)
                'type' => $this->getNotificationType($n),
                
                // جلب معرف العنصر المرتبط (رقم الطلب، أو رقم طلب الصيانة، إلخ)
                'related_id' => $n->order_id ?: $n->maintenance_request_id ?: $n->notifiable_id,
                
                // تنسيق الوقت للعرض البشري (مثال: "منذ 5 دقائق")
                'time' => $n->created_at->diffForHumans(),
                
                // تنسيق ذكي للتاريخ (اليوم، أمس، أو عرض التاريخ العادي)
                'date' => $n->created_at->isToday() ? 'اليوم' : ($n->created_at->isYesterday() ? 'أمس' : $n->created_at->format('Y-m-d')),
                
                'target_role' => $n->target_role,
                'created_at' => $n->created_at->toDateTimeString(),
            ];
        });

        // 4. إرسال الاستجابة (Response) على شكل JSON للتطبيق
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * تحديث حالة إشعار معين ليصبح "مقروء" (Mark a notification as read).
     * (يتم استدعاء هذا المسار عندما ينقر المستخدم على إشعار في التطبيق)
     */
    public function markAsRead($id, Request $request)
    {
        $user = $request->user();
        
        // البحث عن الإشعار المطلوب، والتأكد أنه يخص نفس المستخدم الذي يحاول تحديثه (للحماية)
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        // تحديث الحقل is_read إلى true (أي تم القراءة)
        if ($notification) {
            $notification->update(['is_read' => true]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * جعل جميع إشعارات المستخدم "مقروءة" دفعة واحدة (Mark all notifications as read).
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        // جلب جميع الإشعارات التي تخص هذا المستخدم والتي مازالت غير مقروءة، وتحديثها لـ true
        Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * دالة مساعدة (Helper) لتحديد نوع الإشعار بناءً على بياناته.
     * الفائدة: تساعد مبرمج تطبيق Flutter في معرفة أي شاشة يفتح عندما يضغط المستخدم على الإشعار.
     */
    private function getNotificationType($n)
    {
        // 1. التحقق إن كان النوع محدداً بشكل صريح في قاعدة البيانات
        if ($n->notifiable_type === 'store_rejected') return 'store_rejected'; // رفض متجر
        if ($n->notifiable_type === 'provider_rejected') return 'provider_rejected'; // رفض مهني
        if ($n->notifiable_type === 'store_approved') return 'store_approved'; // قبول متجر
        if ($n->notifiable_type === 'provider_approved') return 'provider_approved'; // قبول مهني

        // 2. إذا لم يكن النوع صريحاً، حاول الاستنتاج من العلاقات (Relations)
        // إذا كان هناك معرف للطلب (order_id) أو المودل هو الطلب، فهو إشعار بخصوص طلبات المتاجر
        if ($n->order_id || $n->notifiable_type == 'App\Models\Order') {
            return 'order';
        }
        // إذا كان يخص طلب صيانة
        if ($n->maintenance_request_id || $n->notifiable_type == 'App\Models\MaintenanceRequest') {
            return 'maintenance';
        }

        // 3. الخطة البديلة الأخيرة: استنتاج النوع من خلال قراءة النص نفسه! (Keyword Detection)
        $title = mb_strtolower($n->title ?? ''); // تحويل النص لأحرف صغيرة في حال كان بالإنجليزية
        if (str_contains($title, 'رفض') && str_contains($n->message ?? '', 'متجر')) return 'store_rejected';
        if (str_contains($title, 'رفض')) return 'provider_rejected';
        if (str_contains($title, 'قبول') && str_contains($n->message ?? '', 'تاجر')) return 'store_approved';
        if (str_contains($title, 'قبول')) return 'provider_approved';

        // إذا لم تنطبق عليه أي حالة، اعتبره إشعاراً عاماً (مثل إعلان للمنصة)
        return 'general';
    }
}
