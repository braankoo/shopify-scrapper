<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('product_id', false, true);
            $table->integer('site_id', false, true);
            $table->char('title', 255);
            $table->integer('position');
            $table->char('type', 255);
            $table->char('image', 255)->nullable();
            $table->char('handle', 255);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('published_at');
            $table->timestamp('first_scrape')->nullable();
            $table->unique([ 'product_id' ], 'prod_cat_unique');
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
