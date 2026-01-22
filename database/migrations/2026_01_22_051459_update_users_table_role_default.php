<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث جميع المستخدمين الذين لديهم 'author' إلى 'user'
        DB::table('users')->where('role', 'author')->update(['role' => 'user']);
        
        // تحديث جميع المستخدمين الذين لديهم null إلى 'user'
        DB::table('users')->whereNull('role')->update(['role' => 'user']);
        
        // في SQLite، enum يتم تنفيذه كـ CHECK constraint
        // يجب إعادة إنشاء الجدول لتحديث CHECK constraint
        if (DB::getDriverName() === 'sqlite') {
            // نسخ البيانات
            $users = DB::table('users')->get()->toArray();
            
            // إسقاط الجدول
            Schema::dropIfExists('users');
            
            // إعادة إنشاء الجدول مع enum محدث
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['admin', 'editor', 'user', 'author'])->default('user');
                $table->rememberToken();
                $table->timestamps();
            });
            
            // إعادة إدراج البيانات
            foreach ($users as $user) {
                $role = ($user->role === 'author' || empty($user->role)) ? 'user' : $user->role;
                DB::table('users')->insert([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'role' => $role,
                    'remember_token' => $user->remember_token,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            }
        } else {
            // لـ MySQL، يمكننا استخدام ALTER TABLE
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'user', 'author') DEFAULT 'user'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // تحديث جميع المستخدمين الذين لديهم 'user' إلى 'author'
        DB::table('users')->where('role', 'user')->update(['role' => 'author']);
    }
};
