<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ProductService;
use App\Services\PromotionService;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Đăng ký ProductService
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService();
        });

        // Đăng ký PromotionService
        $this->app->singleton(PromotionService::class, function ($app) {
            return new PromotionService(
                $app->make(ProductService::class)  // Inject ProductService vào PromotionService
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}