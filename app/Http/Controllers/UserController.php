<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $users = User::withCount(['likes', 'favorites', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:user,editor,admin'],
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'role.required' => 'الدور مطلوب',
            'role.in' => 'الدور غير صحيح',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $roleLabel = match ($validated['role']) {
            'admin' => 'مدير',
            'editor' => 'محرر',
            default => 'مستخدم',
        };

        return back()->with('success', "تم إنشاء الحساب ({$roleLabel}) بنجاح");
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:user,editor,admin'],
        ], [
            'role.required' => 'الدور مطلوب',
            'role.in' => 'الدور غير صحيح',
        ]);

        if ($user->id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك تغيير دورك بنفسك',
                ], 403);
            }

            return back()->with('error', 'لا يمكنك تغيير دورك بنفسك');
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح',
                'role' => $user->role,
                'role_label' => $user->role === 'admin' ? 'مدير' : ($user->role === 'editor' ? 'محرر' : 'مستخدم'),
            ]);
        }

        return back()->with('success', 'تم تحديث الدور بنجاح');
    }
}
