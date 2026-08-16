<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('gmail', fn () => new GmailApiTransport(
            config('services.google_mail.client_id'),
            config('services.google_mail.client_secret'),
            config('services.google_mail.refresh_token'),
        ));
    }
}
