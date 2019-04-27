<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Transformers\UserTransformer;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;   // To access the Users with OAuth
    
    const VERIFIED_USER = '1';
    const UNVERIFIED_USER = '0';

    const ADMIN_USER = 'true';
    const REGULAR_USER = 'false';

    // This variable points to the Transformer
    public $transformer = UserTransformer::class;

    // This variable makes sure that the Inherited tables Seller and Buyers inherits the User structure
    protected $table = 'users';

    protected $dates = ['deleted_at'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'email', 
        'password',
        'verified',
        'verification_token',
        'admin',
    ];

    /**
     * The attributes that should be hidden for arrays.
     * The Variables defined inside the $hidden are not visible under the JSON Response that we will send 
     * @var array
     */
    protected $hidden = [
        'password', 
        'remember_token',
        'verification_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setNameAttribute($name) {
        $this->attributes['name'] = strtolower($name);      // Storing name in lowercase in the database
    }

    public function getNameAttribute($name) {           
        return ucwords($name);                              // Getting the name with first character as Capital letter                          
    }       

    public function setEmailAttribute($email) {
        $this->attributes['email'] = strtolower($email);    // Storing the email in lowercase in the database
    }

    public function isVerified() {
        return $this->verified == User::VERIFIED_USER;
    }

    public function isAdmin() {
        return $this->admin == User::ADMIN_USER;
    }

    public static function generateVerificationCode() {
        return str_random(40);
    }
}
