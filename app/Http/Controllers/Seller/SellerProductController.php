<?php

namespace App\Http\Controllers\Seller;

use App\Seller;
use App\User;
use App\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Requests\ValidateSellerProduct;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Storage;
use App\Transformers\ProductTransformer;

class SellerProductController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

         // To make sure that that while updating and storing we are not reveling the original field names to the end user and rather use the transformed names
        $this->middleware('transform.input:' . ProductTransformer::class)->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        $products = $seller->products;
        return $this->showAll($products);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ValidateSellerProduct $request, User $seller)
    {
        $data = $request->all();
        $data['status'] = Product::UNAVAILABLE_PRODUCT;
        $data['image'] = $request->image->store('');    // Laravel is automatically going to generate a name and store it at the default path
        $data['seller_id'] = $seller->id;
        
        $product = Product::create($data);
        return $this->showOne($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Seller $seller, Product $product)
    {
        //
        $rules = [
            'quantity' => 'integer|min:1',
            'status' => 'in:' . Product::AVAILABLE_PRODUCT . ',' . Product::UNAVAILABLE_PRODUCT,
            'image' => 'image',
        ];

        $this->validate($request, $rules);
        $this->checkSeller($seller, $product);
        $product->fill($request->only([
            'name',
            'description',
            'quantity',
        ]));

        if ($request->has('status')) {
            $product->status = $request->status;
            
            if ($product->isAvailable() && $product->categories()->count() == 0) {
                return $this->errorResponse('An active product must have at least one category', 409);
            }
        }

        if($request->hasFile('image')) {
            Storage::delete($product->image);

            $product->image = $request->image->store('');
        }

        if ($product->isClean()) {
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $product->save();
        return $this->showOne($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function destroy(Seller $seller, Product $product)
    {
        $this->checkSeller($seller, $product);

        Storage::delete($product->image); // Deleting the Image
        
        $product->delete();
        return $this->showOne($product);
    }

    protected function checkSeller(Seller $seller, Product $product)
    {
        if ($seller->id != $product->seller_id) {
            throw new HttpException(422, 'The specified seller is not the actual seller of the product');            
        }
    }
}
