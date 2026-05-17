<?php

namespace App\Providers;

use App\Events\ItemTransferred;
use App\Listeners\SendTransferNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(ItemTransferred::class, SendTransferNotification::class);
    }
}
