<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $frontend = config('app.frontend_url');
            if (is_string($frontend) && $frontend !== '') {
                return rtrim($frontend, '/').'/reset-password/'.$token.'?email='.$email;
            }

            return url('/reset-password/'.$token.'?email='.$email);
        });

        // إجبار استخدام HTTPS في الإنتاج
        if (config('app.env') === 'production' || request()->secure()) {
            \URL::forceScheme('https');
        }
    }
}
