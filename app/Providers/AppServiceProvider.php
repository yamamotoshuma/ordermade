<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Laravel\Pennant\Feature;

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
        //
        Feature::define('attendances-management', function (User $user) {
            if($user->role == 10){
                return true;
            }else if($user->role == 10){
                return false;
            }else{
                return false;
            }
            
        });
    }
}
