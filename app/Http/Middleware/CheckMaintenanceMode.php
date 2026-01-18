<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من وضع الصيانة
        $maintenanceMode = Setting::getValue('maintenance_mode', '0') === '1';
        
        // إذا كان وضع الصيانة مفعل والمستخدم غير مسجل، توجيهه لصفحة الصيانة
        if ($maintenanceMode && !auth()->check()) {
            // السماح بالوصول لصفحة الصيانة وصفحة تسجيل الدخول
            if (!$request->is('maintenance') && !$request->is('login') && !$request->is('logout')) {
                return redirect()->route('maintenance');
            }
        }
        
        return $next($request);
    }
}
