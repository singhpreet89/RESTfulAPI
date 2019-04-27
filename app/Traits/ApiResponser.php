<?php
namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

trait ApiResponser {
    private function successResponse($data, $code) {
        return response()->json($data, $code);
    }

    protected function errorResponse($message, $code) {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }

    protected function showAll(Collection $collection, $code = 200) {
        if($collection->isEmpty()) {
            return $this->successResponse(['data' => $collection], $code);
        }

        $transformer = $collection->first()->transformer;               // VARIABLE, transformer is a public attribute defined in each Model which is pointing to the Each Transformer class

        /* OPTIONAL METHODS */
        $collection = $this->filterData($collection, $transformer);
        $collection = $this->sortData($collection, $transformer);
        $collection = $this->paginate($collection);
        $collection = $this->transformData($collection, $transformer);
        $collection = $this->cacheResponse($collection);                // This will cache the results for 30 seconds, MIGHT NOT WORK
        /* END OPTIONAL METHODS */

        return $this->successResponse($collection, $code);
    }

    protected function showOne(Model $model, $code = 200) {
        $transformer = $model->transformer;
        $model = $this->transformData($model, $transformer);

        return $this->successResponse($model, $code);
    }

    protected function showMessage($message, $code = 200) {
        return $this->successResponse(['data' => $message], $code);
    }

    protected function filterData(Collection $collection, $transformer) {
        foreach (request()->query() as $query => $value) {
            $attribute = $transformer::originalAttribute($query);

            if(isset($attribute, $value)) {
                $collection = $collection->where($attribute, $value);
            }
        } 
        
        return $collection;
    }

    protected function sortData(Collection $collection, $transformer) {
        if(request()->has('sort_by')) {
            $attribute = $transformer::originalAttribute(request()->sort_by);

            $collection = $collection->sortBy->{$attribute};
        }

        return $collection;
    }

    protected function paginate(Collection $collection) {
        /* Allowing the custom page size */
        $rules = [
            'per_page' => 'integer|min:2|max:50',                                       // Minimum and maximum elements per page
        ];

        /* We are going to use the Validation which is independent of Controller by IMPORTING VALIDATOR FACADE */
        Validator::validate(request()->all(), $rules);

        $page = LengthAwarePaginator::resolveCurrentPage();                             // To find which page we are on
        $perPage = 15;                                                                  // Each page will have 15 results only
        
        if(request()->has('per_page')) {
            $perPage = (int)request()->per_page;
        }

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();      // Because the first index of collection is 0, but LengthAwarePaginator will start from Page 1

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(), // resolveCurrentPath() will generate the path for another page in the META shown in the GET request using POSTMAN at the end
        ]); // If we use this, then the parameters which we send will be ignored: such as sort_by will be ignored

        $paginated->appends(request()->all());                                          // So we also append the pther request parameters

        return $paginated;
    }

    protected function transformData($data, $transformer) {
        $transformation = fractal($data, new $transformer);
        
        return $transformation->toArray();
    }

    protected function cacheResponse($data) {   // This is receiving an array, because the transformData is returning an ARRAY
    /* When we have the list of all the users from the API, then we delete the user with (i.e. id=1) then if we try to get the list of all users again 
     * This will still give us the old list, because the results are cached for 30 seconds, if we send the GET request to get all the users again after
     * 30 seconds, then we will get the refreshed list. http://restfulapi.test/users?page=3&sort_by=name (THIS WILL ALSO WORK)
     */    
    $url = request()->url();
    $queryParams = request()->query();              // Getting the parameters from the URL  
    ksort($queryParams);                            // To make sure that the order of the paramteres given in the url has no ipact on the result, sort_by can come before user=1 and vice versa 
    $queryString = http_build_query($queryParams);  // Building the query
    $fullUrl = "{$url}?{$queryString}";             // Creating the complete URL
    
    // Definition of CACHE ===== Cache::remember('key', $minutes, function () {});
    return Cache::remember($fullUrl, 30/60, function() use($data){ // RETURNING THE ARRAY
        return $data;
    });

    }
}