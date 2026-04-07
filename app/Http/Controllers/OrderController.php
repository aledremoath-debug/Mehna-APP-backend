<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Notification;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'provider_id' => 'nullable|exists:service_providers,id',
            'buyer_type' => 'required|in:user,provider',
            'seller_id' => 'nullable|exists:sellers,id',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'in:pending,processing,completed,cancelled',
            'total_price' => 'required|numeric',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الطلب غير صالحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق لمنع الشراء من نفس المتجر 
        if ($request->seller_id) {
            $seller = \App\Models\Seller::find($request->seller_id);
            if ($seller && $seller->user_id == $request->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا يمكنك شراء منتجات من متجرك الخاص.'
                ], 403);
            }
        }

        // التحقق لمنع طلب خدمة من نفس مزود الخدمة
        if ($request->provider_id) {
            $provider = \App\Models\ServiceProvider::find($request->provider_id);
            if ($provider && $provider->user_id == $request->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا يمكنك طلب خدمة من حسابك الخاص كـمزود خدمة.'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $request->user_id,
                'provider_id' => $request->provider_id,
                'buyer_type' => $request->buyer_type,
                'seller_id' => $request->seller_id,
                'description' => $request->description,
                'total_price' => $request->total_price,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => $request->status ?? 'pending',
            ]);

            foreach ($request->products as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("الكمية المطلوبة غير متوفرة للمنتج: " . $product->product_name);
                }

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                // تحديث المخزون
                $product->decrement('stock_quantity', $item['quantity']);
            }

            DB::commit();

            // إشعار التاجر بطلب جديد إذا وجد
            if ($order->seller_id) {
                $seller = Seller::with('user')->find($order->seller_id);
                if ($seller && $seller->user) {
                    $fcm = new FcmService();
                    $title = "طلب شراء جديد";
                    $message = "لديك طلب شراء جديد رقم #{$order->id}";

                    Notification::create([
                        'user_id' => $seller->user->user_id,
                        'order_id' => $order->id,
                        'title' => $title,
                        'message' => $message,
                        'is_read' => false,
                        'notifiable_type' => Order::class,
                        'notifiable_id' => $order->id,
                        'target_role' => 'vendor'
                    ]);

                    if ($seller->user->fcm_token) {
                        $fcm->sendNotification(
                            $seller->user->fcm_token,
                            $title,
                            $message,
                            [
                                'type' => 'new_product_order',
                                'order_id' => $order->id
                            ]
                        );
                    }
                }
            }

            // إشعار العميل بتأكيد الطلب
            $user = $order->user;
            if ($user) {
                $customerTitle = "تم استلام طلبك";
                $customerMessage = "تم تسجيل طلبك رقم #{$order->id} بنجاح وهو قيد المراجعة";

                Notification::create([
                    'user_id' => $user->user_id,
                    'order_id' => $order->id,
                    'title' => $customerTitle,
                    'message' => $customerMessage,
                    'is_read' => false,
                    'notifiable_type' => Order::class,
                    'notifiable_id' => $order->id,
                    'target_role' => 'customer'
                ]);

                if ($user->fcm_token) {
                    $fcm = new FcmService();
                    $fcm->sendNotification(
                        $user->fcm_token,
                        $customerTitle,
                        $customerMessage,
                        [
                            'type' => 'product_order_status',
                            'order_id' => $order->id
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'order' => $order->load('orderDetails.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب طلبات العميل (المنتجات)
     * GET /api/my-orders
     */
    public function myOrders(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->user_id)
            ->with(['orderDetails.product.images', 'seller'])
            ->latest()
            ->get();

        $data = $orders->map(function ($order) use ($user) {
            return [
                'id'          => $order->id,
                'type'        => 'product',
                'status'      => $order->status,
                'is_reviewed' => $order->reviews()->where('rater_id', $user->user_id)->exists(),
                'total_price' => number_format($order->total_price, 2, '.', ''),
                'location'    => $order->location,
                'date'        => $order->created_at?->toDateTimeString(),
                'store_name'  => $order->seller?->shop_name ?? 'متجر',
                'seller_user_id' => $order->seller?->user_id,
                'items'       => $order->orderDetails->map(function ($d) {
                    $img = null;
                    if ($d->product && $d->product->images->isNotEmpty()) {
                        $img = asset('media/' . $d->product->images->first()->image_path);
                    }
                    return [
                        'product_id'   => $d->product_id,
                        'product_name' => $d->product?->product_name ?? 'منتج محذوف',
                        'quantity'     => $d->quantity,
                        'unit_price'   => number_format($d->unit_price, 2, '.', ''),
                        'image'        => $img,
                    ];
                }),
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * إلغاء طلب منتج (pending فقط)
     * POST /api/orders/{id}/cancel
     */
    public function cancelOrder(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->user_id)
            ->with('orderDetails.product')
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'لا يمكن إلغاء الطلب في هذه المرحلة'], 422);
        }

        try {
            DB::beginTransaction();

            // إعادة الكميات للمخزون
            foreach ($order->orderDetails as $detail) {
                if ($detail->product) {
                    $detail->product->increment('stock_quantity', $detail->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'تم إلغاء الطلب بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'فشل الإلغاء: ' . $e->getMessage()], 500);
        }
    }
}
