<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        // التحقق من أن المستخدم موجود وليس لديه google_id فقط
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && $user->google_id && !$user->password) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الحساب مسجل باستخدام Google. يرجى تسجيل الدخول باستخدام Google',
                    'errors' => ['email' => ['هذا الحساب مسجل باستخدام Google']]
                ], 422);
            }
            
            throw ValidationException::withMessages([
                'email' => __('هذا الحساب مسجل باستخدام Google. يرجى تسجيل الدخول باستخدام Google'),
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'redirect' => route('home')
                ]);
            }
            
            return redirect()->intended('/dashboard');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => ['email' => ['بيانات الدخول غير صحيحة']]
            ], 422);
        }

        throw ValidationException::withMessages([
            'email' => __('بيانات الدخول غير صحيحة'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}


