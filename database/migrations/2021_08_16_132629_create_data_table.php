<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'data',
            function (Blueprint $table) {


                $table->char('site', 255);
                $table->char('catalog', 255);
                $table->char('product', 255);

                $table->integer('site_id');
                $table->text('image');
                $table->char('url', 255);
                $table->char('type', 255);

                $table->dateTime('created_at');
                $table->dateTime('published_at');

                $table->integer('position')->nullable();
                $table->integer('sales')->nullable();
                $table->bigInteger('quantity');
                $table->bigInteger('product_id');
                $table->date('date_created');
                $table->primary([ 'product_id', 'date_created' ]);


            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data');
    }
}
