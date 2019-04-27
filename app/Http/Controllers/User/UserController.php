<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\User;
use App\Http\Requests\ValidateUser;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreated;
use App\Transformers\UserTransformer;

class UserController extends ApiController
{
    public function __construct()
    {
        // oAuth Authorization 
        // $this->middleware('client.credentials')->only(['store', 'resend']);

         // To make sure that that while updating and storing we are not reveling the original field names to the end user and rather use the transformed names
        $this->middleware('transform.input:' . UserTransformer::class)->only(['store', 'update']); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        
        // return response()->json(['data' => $users], 200);   // This will return a JSON array named data, JSON response CODE = 200 means that it is OK
        // OR BY USING THE TRAITS
        return $this->showAll($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ValidateUser $request)
    {
        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $data['verified'] = User::UNVERIFIED_USER;                      // To stop the User from sending the verified as 1
        $data['verification_token'] = User::generateVerificationCode();
        $data['admin'] = User::REGULAR_USER;                            // To stop the User from sending the user type as ADMIN                           

        $user = User::create($data);    // MASS ASSIGNMENT

        // return response()->json(['data', $user], 201);
        // OR BY USING THE TRAITS
        return $this->showOne($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function show($id)
    // {
    //     $user = User::findOrFail($id);
        
    //     // return response()->json(['data' => $user], 200);
    //     // OR BY USING THE TRAITS
    //     return $this->showOne($user);
    // }
    
    /* IMPLICIT BINDING on SHOW METHOD (Same as above) */
    public function show(User $user)
    {
        // Here, LARAVEL will automatically resolve the instance sent from the front end and we need not to specify the id 

        // return response()->json(['data' => $user], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
    /* IMPLICIT BINDING can also be applied here on REQUEST METHOD 
     * By replacing $id with User $user and then removing the $user = User::findOrFail($id);
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            /* Validation says that the email must be unique, but what happens if a user sends an update request using his own email.
             * So laravel is going to say that, this email already exists, we need to accept the user that is performing the request.
             * Let us suppose that the user that is performing the request, is the same specified by the id, for that we need to validate
             * the email and accepting the user with their received id
            */
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'min:6|confirmed',
            'admin' => 'in:' . User::ADMIN_USER . ',' . User::REGULAR_USER,
        ];

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email') && $user->email != $request->email) {
            $user->verified = User::UNVERIFIED_USER;
            $user->verification_token = User::generateVerificationCode();
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        if ($request->has('admin')) {
            if (!$user->isVerified()) {
                // return response()->json(['error' => 'Only verified users can modify the admin field', 'code' => 409], 409);
                // OR
                return $this->errorResponse('Only verified users can modify the admin field', 409);
            }
            $user->admin = $request->admin;
        }

        // To check if the User is not Updated
        if (!$user->isDirty()) {
            // return response()->json(['error' => 'You need to specify a different value to update', 'code' => 422], 422);
            // OR
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $user->save();

        // return response()->json(['data' => $user], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy($id)
    // {
    //     $user = User::findOrFail($id);
    //     $user->delete();

    //     // return response()->json(['data' => $user], 200);
    //     // OR BY USING THE TRAITS
    //     return $this->showOne($user);
    // }
    
    /* IMPLICIT BINDING on DESTROY METHOD (Same as above) */
    public function destroy(user $user)
    {
        $user->delete();
        // return response()->json(['data' => $user], 200);
        // OR BY USING THE TRAITS
        return $this->showOne($user);
    }

    public function verify($token) {
        $user = User::where('verification_token', $token)->firstOrFail();
       
        $user->verified = User::VERIFIED_USER;
        $user->verification_token = null;
        $user->save();

        // return response()->json('This account has been verified successfully', 200);
        // OR BY USING THE TRAITS
        return $this->showMessage('This account has been verified successfully');
    }

    public function resend(User $user) {
        if($user->isVerified()) {
            return $this->errorResponse('This user is already verified', 409);
        }

        retry(5, function() use ($user) {
            Mail::to($user)->send(new UserCreated($user));
        }, 100); 
        
        return $this->showMessage('The verification email has been resend');
    }
}
