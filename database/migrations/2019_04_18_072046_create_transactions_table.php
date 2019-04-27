<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('quantity')->unsigned();
            $table->bigInteger('buyer_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();                                              // Creates a new field as Deleted at in the database
            
            /* Since Buyer Inherits User and Buyer itself does not have any id
             * So the Foreign key contraint has to be the user's id 
             */
            $table->foreign('buyer_id')->references('id')->on('users');         // We need to mention the users Table here not the User Model
            $table->foreign('product_id')->references('id')->on('products');    // We need to mention the products Table here not the Product Model
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
