<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatalogsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('catalog_id');
            $table->integer('site_id', false, true);
            $table->char('handle', 255);
            $table->char('title', 255);
            $table->char('description', 255);
            $table->timestamp('published_at');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->unique([ 'catalog_id', 'site_id' ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('catalogs');
    }
}
