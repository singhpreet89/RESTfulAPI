<?php

namespace App\Http\Controllers\Category;

use App\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Requests\ValidateCategory;
use App\Transformers\CategoryTransformer;

class CategoryController extends ApiController
{
    public function __construct()
    {
        // oAuth Authorization 
        // $this->middleware('client.credentials')->only(['index', 'show']);
        // parent::__construct();
        // To make sure that that while updating and storing we are not reveling the original field names to the end user and rather use the transformed names
        $this->middleware('transform.input:' . CategoryTransformer::class)->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::all();

        // return response()->json(['data' => $categories], 200);   // This will return a JSON array named data, JSON response CODE = 200 means that it is OK
        // OR BY USING THE TRAITS
        return $this->showAll($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ValidateCategory $request)
    {
        $category = $request->all();                          

        $newCategory = Category::create($category);    // MASS ASSIGNMENT

        // return response()->json(['data', $newCategory], 201);
        // OR BY USING THE TRAITS
        return $this->showOne($newCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        // Here, LARAVEL will automatically resolve the instance sent from the front end and we need not to specify the id 

        // return response()->json(['data' => $category], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        // Adding only the name and description sent by the user to $category as a key value pair
        $category->fill($request->only(['name', 'description',]));

        // If Category does not change (If the User has not sent the new and updated data and sent the same data which is already present in the table) then generate the error
        if ($category->isClean()) {
            return $this->errorResponse('You need to specify any different value to update', 422);
        }
        $category->save();

        // return response()->json(['data' => $category], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $category->delete();
        
        // return response()->json(['data' => $category], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($category);
    }
}
