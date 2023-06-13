<?php

namespace App\Providers;

use App\Services\Core\AuthService;
use App\Services\MinioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
        $this->app->bind(MinioService::class, function ($app) {
            return new MinioService($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $getSetting = DB::table('settings')->get();

        foreach ($getSetting as $value) {
            if ($value->key == 'MINIO_KEY') {
                config(['filesystems.disks.s3.key' => $value->value]);
            }
            if ($value->key == 'MINIO_SECRET') {
                config(['filesystems.disks.s3.secret' => $value->value]);
            }
            if ($value->key == 'MINIO_ENDPOINT') {
                config(['filesystems.disks.s3.endpoint' => $value->value]);
            }
            if ($value->key == 'MINIO_BUCKET') {
                config(['filesystems.disks.s3.bucket' => $value->value]);
            }
            if ($value->key == 'MINIO_URL') {
                config(['filesystems.disks.s3.url' => $value->value]);
            }
            if ($value->key == 'SITE_NAME') {
                config(['app.name' => $value->value]);
            }
        }

    }
}
