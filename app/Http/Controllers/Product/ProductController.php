<?php

namespace App\Http\Controllers\Product;

use App\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class ProductController extends ApiController
{
    public function __construct()
    {
        // oAuth Authorization 
        // $this->middleware('client.credentials')->only(['index', 'show']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();

        // return response()->json(['data' => $products], 200);   // This will return a JSON array named data, JSON response CODE = 200 means that it is OK
        // OR BY USING THE TRAITS
        return $this->showAll($products);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        // Here, LARAVEL will automatically resolve the instance sent from the front end and we need not to specify the id 

        // return response()->json(['data' => $product], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($product);
    }
}
