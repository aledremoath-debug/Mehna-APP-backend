<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Service;
use App\Models\User;
use App\Models\UserPassword;
use App\Models\ServiceProvider;

class ServiceHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // 1. تنظيف الجداول قبل البدء
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MainCategory::truncate();
        SubCategory::truncate();
        Service::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. إنشاء مزود خدمة للاختبار (إذا لم يكن موجوداً)
        $providerUser = User::where('email', 'provider@test.com')->first();
        if (!$providerUser) {
            $providerUser = User::create([
                'full_name'   => 'محمد الفني',
                'email'       => 'provider@test.com',
                'phone'       => '777222222',
                'user_type'   => 1, // مقدم خدمة
                'location_id' => 1,
            ]);
            UserPassword::create([
                'user_id'       => $providerUser->user_id,
                'password_hash' => Hash::make('password123'),
            ]);
        }

        $providerProfile = ServiceProvider::where('user_id', $providerUser->user_id)->first();
        if (!$providerProfile) {
            $providerProfile = ServiceProvider::create([
                'user_id'           => $providerUser->user_id,
                'bio'              => 'فني متخصص في الصيانة المنزلية والكهرباء',
                'experience_years' => 5,
                'is_available'     => true,
            ]);
        }

        // 3. البيانات المنطقية الدقيقة حسب طلب المستخدم
        $categories = [
            [
                'name' => 'سباكة',
                'image' => 'plumbing.jpg',
                'subs' => [
                    [
                        'name' => 'صيانة وتحسين الحمامات',
                        'services' => [
                            ['title' => 'صيانة وتثبيت كراسي الحمام (Toilets)', 'price' => 2000, 'desc' => 'تركيب وتثبيت كراسي الحمام بجميع أنواعها مع ضمان عدم التسريب'],
                            ['title' => 'إصلاح تسريبات الشاور والدش', 'price' => 1500, 'desc' => 'كشف وإصلاح تسريبات المياه في كبائن الشاور والحمامات'],
                            ['title' => 'تركيب وتغيير المغاسل (Sinks)', 'price' => 1200, 'desc' => 'فك وتثبيت مغاسل المطبخ والحمامات مع تمديد الليات'],
                        ]
                    ],
                    [
                        'name' => 'فلاتر ومعالجة المياه',
                        'services' => [
                            ['title' => 'تركيب فلاتر مياه منزلية', 'price' => 2500, 'desc' => 'تركيب أنظمة تحلية المياه وفلاتر الشرب بمراحل مختلفة'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'كهرباء',
                'image' => 'electricity.jpg',
                'subs' => [
                    [
                        'name' => 'إضاءة وتجهيزات كهربائية',
                        'services' => [
                            ['title' => 'تركيب النجف والثريات', 'price' => 3000, 'desc' => 'تركيب النجف الكبير والثريات المعلقة باحترافية وأمان'],
                            ['title' => 'تغيير المفاتيح الكهربائية والأفياش', 'price' => 500, 'desc' => 'تغيير المفاتيح التالفة وتركيب أفياش كهربائية جديدة'],
                        ]
                    ],
                    [
                        'name' => 'تأسيس وصيانة القواطع',
                        'services' => [
                            ['title' => 'إصلاح القواطع الرئيسية', 'price' => 4500, 'desc' => 'فحص اللوحات الرئيسية وتبديل القواطع المتعطلة لضمان استقرار التيار'],
                            ['title' => 'تمديد أسلاك كهربائية جديدة', 'price' => 6000, 'desc' => 'تمديد وتأسيس نقاط كهرباء جديدة في الغرف'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'صيانة الأجهزة',
                'image' => 'appliances.jpg',
                'subs' => [
                    [
                        'name' => 'أجهزة التبريد والتكييف',
                        'services' => [
                            ['title' => 'صيانة الثلاجات', 'price' => 3500, 'desc' => 'تعبئة فريون الثلاجات وفحص الكمبروسر والترموستات'],
                            ['title' => 'صيانة مكيفات (سبليت/شباك)', 'price' => 4000, 'desc' => 'تنظيف وصيانة شاملة للمكيفات وتعبئة الغاز'],
                        ]
                    ],
                    [
                        'name' => 'الأجهزة المنزلية الكبيرة',
                        'services' => [
                            ['title' => 'صيانة غسالات ملابس', 'price' => 2800, 'desc' => 'إصلاح أعطال التصريف والمحركات في الغسالات العادية والاتوماتيك'],
                            ['title' => 'صيانة وإصلاح الأفران', 'price' => 2200, 'desc' => 'تنظيف عيون الغاز وإصلاح حساسات الأفران والاشعال الذاتي'],
                        ]
                    ],
                    [
                        'name' => 'الإلكترونيات والترفيه',
                        'services' => [
                            ['title' => 'صيانة التلفزيونات والشاشات', 'price' => 5000, 'desc' => 'إصلاح أعطال الصورة والصوت واللوحات الرئيسية للشاشات'],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($categories as $catData) {
            $mainCat = MainCategory::create([
                'name' => $catData['name'],
                'image' => $catData['image']
            ]);

            foreach ($catData['subs'] as $subData) {
                $subCat = SubCategory::create([
                    'main_category_id' => $mainCat->id,
                    'name' => $subData['name']
                ]);

                foreach ($subData['services'] as $serData) {
                    Service::create([
                        'main_category_id'     => $mainCat->id,
                        'sub_category_id'      => $subCat->id,
                        'service_provider_id' => $providerProfile->id,
                        'title'                => $serData['title'],
                        'price'                => $serData['price'],
                        'description'          => $serData['desc']
                    ]);
                }
            }
        }
    }
}
