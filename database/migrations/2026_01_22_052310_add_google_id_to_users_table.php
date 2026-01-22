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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
        });

        // في SQLite، يجب إعادة إنشاء الجدول لجعل password nullable
        if (DB::getDriverName() === 'sqlite') {
            // نسخ البيانات
            $users = DB::table('users')->get()->toArray();
            
            // إسقاط الجدول
            Schema::dropIfExists('users');
            
            // إعادة إنشاء الجدول مع password nullable
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('google_id')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->enum('role', ['admin', 'editor', 'user', 'author'])->default('user');
                $table->rememberToken();
                $table->timestamps();
            });
            
            // إعادة إدراج البيانات
            foreach ($users as $user) {
                DB::table('users')->insert([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => null,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'role' => $user->role ?? 'user',
                    'remember_token' => $user->remember_token,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            }
        } else {
            // لـ MySQL
            DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
