<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount(['likes', 'favorites', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,editor,admin'],
        ], [
            'role.required' => 'الدور مطلوب',
            'role.in' => 'الدور غير صحيح',
        ]);

        // منع المستخدم من تغيير دوره بنفسه
        if ($user->id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك تغيير دورك بنفسك'
                ], 403);
            }
            return back()->with('error', 'لا يمكنك تغيير دورك بنفسك');
        }

        $user->update([
            'role' => $validated['role']
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح',
                'role' => $user->role,
                'role_label' => $user->role === 'admin' ? 'مدير' : ($user->role === 'editor' ? 'محرر' : 'مستخدم')
            ]);
        }

        return back()->with('success', 'تم تحديث الدور بنجاح');
    }
}
