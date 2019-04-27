<?php

namespace App\Http\Controllers\Buyer;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Buyer;

class BuyerController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $buyers = Buyer::has('transactions')->get();
        
        // return response()->json(['data' => $buyers], 200);
        // OR BY USING THE TRAITS
        return $this->showAll($buyers); // This method is defined in the Traits\ApiResponser THROUGH API CONTROLLER CLASS
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $buyer = Buyer::has('transactions')->findOrFail($id);
        
        // return response()->json(['data' => $buyer], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($buyer);
    }  
}
