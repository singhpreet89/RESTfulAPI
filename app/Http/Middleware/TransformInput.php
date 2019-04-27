<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Validation\ValidationException;

class TransformInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $transformer)
    {
        // To be filled with new and original names of the database fields
        $transformedInput = [];

        /* Getting only the INPUTS and not the QUERY STRINGS such as sort_by 
         * We need to go deeper to obtain only that.
         * Getting only the elements from the body of the request
         * 
         */
        foreach ($request->request->all() as $input => $value) {
            $transformedInput[$transformer::originalAttribute($input)] = $value; // Calling originalAttribute function in the corresponding Transformer
        }
        
        $request->replace($transformedInput);

        $response =  $next($request);

        // Check if there is a validation exception in the response
        if (isset($response->exception) && $response->exception instanceof ValidationException) {
            $data = $response->getData();   // Obtain the data of the response which is error and Code but we need the content of error so that we can modify it
            
            $transformedErrors = [];
            
            // Go directly to the error element and obtain the content
            foreach ($data->error as $field => $error) {
                $transformedField = $transformer::transformedAttribute($field); // Using the transformer call the transformedAttribute() function
                
                $transformedErrors[$transformedField] = str_replace($field, $transformedField, $error); // Replace inside error the field names with transformed fields
            }

            $data->error = $transformedErrors;
            $response->setData($data);
        }
        return $response;
    }
}
