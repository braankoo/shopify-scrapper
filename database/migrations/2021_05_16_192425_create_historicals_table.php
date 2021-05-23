<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricalsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historicals', function (Blueprint $table) {
            $table->id();

            $table->integer('price')->nullable();
            $table->integer('compare_at_price', false, true)->nullable();
            $table->integer('inventory_quantity', false, false)->nullable()->default(null);
            $table->bigInteger('variant_id', false, true);
            $table->bigInteger('product_id', false, true);
            $table->integer('site_id');
            $table->integer('sales')->nullable();
            $table->date('date_created');
            $table->unique([ 'variant_id', 'product_id', 'date_created' ]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historicals');
    }
}
