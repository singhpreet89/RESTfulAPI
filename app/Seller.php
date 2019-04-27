<?php

namespace App;
use App\User;
use App\Transformers\SellerTransformer;

class Seller extends User
{
    // This variable points to the Transformer
    public $transformer = SellerTransformer::class;

    public function products() {
        return $this->hasMany('App\Product');
    }
}
