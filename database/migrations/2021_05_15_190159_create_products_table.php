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
            $table->enum('active', [ 'true', 'false' ])->default('true');
            $table->char('title', 255);
            $table->char('type', 255);
            $table->char('image_1', 255)->nullable();
            $table->char('image_2', 255)->nullable();
            $table->char('image_3', 255)->nullable();
            $table->char('tags', 255);
            $table->char('link', 255);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('published_at');
            $table->timestamp('first_scrape')->nullable();
            $table->unique([ 'product_id', 'site_id' ], 'prod_cat_unique');
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
