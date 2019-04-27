<?php

namespace App\Http\Controllers\Transaction;

use App\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class TransactionController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transaction = Transaction::all();

        // return response()->json(['data' => $products], 200);   // This will return a JSON array named data, JSON response CODE = 200 means that it is OK
        // OR BY USING THE TRAITS
        return $this->showAll($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
         // Here, LARAVEL will automatically resolve the instance sent from the front end and we need not to specify the id 

        // return response()->json(['data' => $transaction], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($transaction);
    }

}
