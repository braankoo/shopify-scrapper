<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPositionTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_position', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id', false, true);
            $table->integer('position');
            $table->dateTime('date_created');
            $table->unique('product_id', 'date_created');
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_position');
    }
}
