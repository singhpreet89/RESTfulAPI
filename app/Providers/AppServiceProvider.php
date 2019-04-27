<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Product;
use App\User;
use App\Mail\UserCreated;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserMailChanged;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // This event will fireup even when we use factories and run seeder to create fake users
        // So we will disable the event() listeners in the database seeder
        User::created(function($user) {
            /* retry(no_of_times, The actual work to be done, wait in Milliseconds) */
            retry(5, function() use ($user) {
                Mail::to($user)->send(new UserCreated($user)); // We can also write $user->email, but Laravel automatically resolves $user to $user->email
            },100);     
        });

        User::updated(function($user) {
            // Checking if the mail has been updated
            if($user->isDirty('email')) {
                retry(5, function () use ($user) {
                    Mail::to($user)->send(new UserMailChanged($user));    // We can also write $user->email, but Laravel automatically resolves $user to $user->email
                }, 100);
            }
        });
       
        Product::updated(function($product) {
            if($product->quantity == 0 && $product->isAvailable()) {
               $product->status = Product::UNAVAILABLE_PRODUCT;
               
               $product->save();
            }
        });
    }
}
