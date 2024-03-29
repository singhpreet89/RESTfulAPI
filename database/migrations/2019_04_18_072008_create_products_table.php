<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Product;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description', 1000);
            $table->integer('quantity')->unsigned();
            $table->string('status')->default(Product::UNAVAILABLE_PRODUCT);
            $table->string('image');
            $table->bigInteger('seller_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();                                       // Creates a new field as Deleted at in the database 
            
            /* Since Seller Inherits User and Seller itself does not have any id
             * So the Foreign key contraint has to be the user's id
             */
            $table->foreign('seller_id')->references('id')->on('users'); // We need to mention the users Table here not the User Model 
              
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
