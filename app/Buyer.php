<?php

namespace App;

use App\Transaction;
use App\Transformers\BuyerTransformer;

class Buyer extends User
{
    // This variable points to the Transformer
    public $transformer = BuyerTransformer::class;

    public function transactions() {
        return $this->hasMany(Transaction::class);
        // OR
        // return $this->hasMany('App\Transaction'); // And remove the use App\Transaction from the top
    }
}
