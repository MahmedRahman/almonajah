<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // حذف الجدول إذا كان موجوداً مسبقاً (في حالة إعادة التشغيل)
        Schema::dropIfExists('asset_category');
        
        Schema::create('asset_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
            
            // منع التكرار: نفس الـ asset مع نفس الـ category
            $table->unique(['asset_id', 'category_id']);
        });

        // نقل البيانات من content_category إلى pivot table
        // نقرأ كل assets التي لها content_category ونربطها بالـ categories
        if (Schema::hasColumn('assets', 'content_category')) {
            $assets = DB::table('assets')
                ->whereNotNull('content_category')
                ->where('content_category', '!=', '')
                ->select('id', 'content_category')
                ->get();

            foreach ($assets as $asset) {
                // البحث عن category بنفس الاسم
                $category = DB::table('categories')
                    ->where('name', $asset->content_category)
                    ->first();

                if ($category) {
                    // إضافة العلاقة في pivot table
                    DB::table('asset_category')->insertOrIgnore([
                        'asset_id' => $asset->id,
                        'category_id' => $category->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // إذا لم نجد التصنيف، نتحقق من slug أولاً قبل الإنشاء
                    $slug = \Illuminate\Support\Str::slug($asset->content_category);
                    $existingCategory = DB::table('categories')
                        ->where('slug', $slug)
                        ->first();
                    
                    if ($existingCategory) {
                        // استخدام التصنيف الموجود
                        $categoryId = $existingCategory->id;
                    } else {
                        // إنشاء تصنيف جديد فقط إذا لم يكن موجوداً
                        $categoryId = DB::table('categories')->insertGetId([
                            'name' => $asset->content_category,
                            'slug' => $slug,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('asset_category')->insertOrIgnore([
                        'asset_id' => $asset->id,
                        'category_id' => $categoryId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_category');
    }
};
