<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponser;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        // When the validations fail and user does not provide the required parameters
        if ($exception instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        // When the User does not exist in the database
        if($exception instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("The {$modelName} with the specified identificator does not exist", 404);
        }

        // When the user is not authenticated
        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        // When a user is trying to modify something but he does not have the privillages
        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse($exception->getMessage(), 403);
        } 

        // When a user sends a request to the URL which exist but with a wrong HTTP method, Sending a POST request for a GET route
        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method for the request is invalid', 405);
        }
        
        // When the user is trying to access a URL that does not exist
        if ($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('The specified URL cannot be found', 404);
        }

        // GENERAL rule to handle any other types of HTTP Exceptions
        if ($exception instanceof HttpException) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        // (FOREIGN KEY constraint Violation), When we are trying to delete a user from the database that is related to a transaction or a product in the database
        if ($exception instanceof QueryException) {
            /* 
             * If we write
             * dd($sxception); // DEBUGGGING, Shows all the details of the Query Exception
             
             * After DEBUGGING ENABLED, OPEN POSTMAN
             * Check the Response of DELETE type request: http://restfulapi.test/users/1 in POSTMAN
             * Inside the resonse, There is aan ARRAY errorInfo:, whose index [1] shows the ERROR CODE,
               Thats why we have written: $exception->errorInfo[1]; to check the ERROR CODE
               The code 1451 is returned in case of FOREIGN KEY VIOLATION in the database
             */
            $errorCode = $exception->errorInfo[1];  // 
            if ($errorCode == 1451) {
                return $this->errorResponse('Cannot remove this resource permanently. It is related with other resource', 409);
            }
        }

        /* If the CSRF token mismatch is found (Because we dont want to provide any information to the attacker)
         * Test by Page Inspect, Go to the FORM and Manually delete the token
         */
        if ($exception instanceof TokenMismatchException) {
            return redirect()->back()->withInput($request->input()); // Returning the user back to the login page with Email address still in the field
        }

       /* FOR: UNEXPECTED EXCEPTIONS:
        * Database if down (Anything which required Database is going to fail), and we are trying to obtain the list of users in the database 
        * We cannot establish a connection with 3rd Party Service 
        * WHEN WE ADD THE APPLICATION ON THE PRODUCTION SERVER:
          The, APP_DEBUG=true should be set to APP_DEBUG=false in the .env file
        */ 
        // When the APP_DEBUG=true in the .env file, We are still developing and are on the DEVELOPMENT SERVER and not ON THE PRODUCTION SERVER, then we will generate the detailed response
        if (config('app.debug')) {
            
            // For more information of the Exception above in the of conditions: Check th differet types of exception in the reder() function 
            return parent::render($request, $exception);            
        }

        // When the APP_DEBUG=false in the .env file, We have already deployed the application on the PRODUCTION SERVER, then generate a Generic Response
        return $this->errorResponse('Unexpected Exception. Try later', 500);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Checking if the exception is coming from the FRONT END
        if($this->isFrontend($request)) {
            return redirect()->guest('login');
        }

        return $this->errorResponse('Unauthenticated.', 401);
    }

     /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->validator->errors()->getMessages();

        // Checking if the exception is coming from the FRONT END
        if($this->isFrontend($request)) {
            return $request->ajax() ? response()->json($errors, 422) : redirect()
            ->back()
            ->withInput()
            ->withErrors($errors);
        }

        return $this->errorResponse($errors, 422);
    }

    private function isFrontend($request) {

        // Checking if the request is an HTML  request, using collections, fing the middleware corresponding to the route and check if it contains 'web'
        return $request->acceptsHtml() && collect($request->route()->middleware())->contains('web');
    }
}
