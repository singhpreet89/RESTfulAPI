<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Transaction;
use Faker\Generator as Faker;
use App\Seller;
use App\User;

$factory->define(Transaction::class, function (Faker $faker) {
   
    $seller = Seller::has('products')->get()->random();     // Select only those sellers who have products
    $buyer = User::all()->except($seller->id)->random();    // Select only those Users who are not Sellers (i.e. Buyers only) 

    return [
        'quantity' => $faker->numberBetween(1, 3),
        'buyer_id' => $buyer->id,
        'product_id' => $seller->products->random()->id,
    ];
});
