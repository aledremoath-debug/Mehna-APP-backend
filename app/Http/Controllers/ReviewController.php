<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rater_id' => 'required|exists:users,user_id',
            'rated_id' => 'required|exists:users,user_id',
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'nullable|string',
            'order_id' => 'nullable|exists:orders,id',
            'maintenance_request_id' => 'nullable|exists:maintenance_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // التحقق من وجود علاقة حقيقية (طلب منتج أو خدمة) وأن الحالة مكتملة
            $isVerified = false;
            
            if ($request->order_id) {
                $isVerified = DB::table('orders')
                    ->where('id', $request->order_id)
                    ->where('user_id', $request->rater_id) // تم تصحيح buyer_id إلى user_id
                    ->where('status', 'completed')
                    ->exists();
            } elseif ($request->maintenance_request_id) {
                $isVerified = DB::table('maintenance_requests')
                    ->where('id', $request->maintenance_request_id)
                    ->where('customer_id', $request->rater_id)
                    ->whereIn('status', ['completed', 'مكتمل']) // Some requests might use Arabic status
                    ->exists();
            }

            if (!$isVerified) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا يمكنك التقييم بدون وجود طلب حقيقي ومكتمل مرتبط بك.'
                ], 403);
            }

            // التحقق من عدم وجود تقييم سابق لنفس الطلب
            $exists = Review::where('rater_id', $request->rater_id)
                ->where(function($query) use ($request) {
                    if ($request->order_id) {
                        $query->where('order_id', $request->order_id);
                    } elseif ($request->maintenance_request_id) {
                        $query->where('maintenance_request_id', $request->maintenance_request_id);
                    }
                })->exists();

            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لقد قمت بتقييم هذا الطلب مسبقاً.'
                ], 403);
            }

            // إنشاء التقييم
            $review = Review::create($request->all());

            // تحديث متوسط التقييم للطرف الآخر (فني أو بائع)
            $this->updateAverageRating($request->rated_id);

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل التقييم بنجاح، شكراً لمساهمتك!',
                'review'  => $review
            ]);

        } catch (\Exception $e) {
            Log::error('Review Store Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء حفظ التقييم، يرجى المحاولة لاحقاً.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function updateAverageRating($rated_id)
    {
        try {
            $stats = Review::where('rated_id', $rated_id)
                ->selectRaw('AVG(rating) as average, COUNT(*) as count')
                ->first();
            
            $average = round($stats->average ?? 0, 2);
            $count = $stats->count ?? 0;

            // تحديث في جدول service_providers إذا كان فنياً
            DB::table('service_providers')
                ->where('user_id', $rated_id)
                ->update([
                    'rating_average' => $average,
                    'rating_count'   => $count
                ]);
            
            // تحديث في جدول sellers إذا كان تاجراً
            DB::table('sellers')
                ->where('user_id', $rated_id)
                ->update([
                    'rating_average' => $average,
                    'rating_count'   => $count
                ]);
            
        } catch (\Exception $e) {
            Log::error('Update Average Rating Error: ' . $e->getMessage());
        }
    }
}
