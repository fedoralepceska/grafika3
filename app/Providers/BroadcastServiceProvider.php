<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use App\Broadcasting\WebSocketBroadcaster;
use App\Services\WebSocketBroadcastService;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes();

        // Register custom WebSocket broadcaster
        Broadcast::extend('websocket', function ($app) {
            return new WebSocketBroadcaster(
                $app->make(WebSocketBroadcastService::class)
            );
        });

        require base_path('routes/channels.php');
    }
}
