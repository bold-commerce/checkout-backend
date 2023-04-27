<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_url', function (Blueprint $table) {
            $table->integer('shop_id')->nullable(false);
            $table->string('back_to_cart_url', 255)->nullable(false);
            $table->string('back_to_store_url', 255)->nullable(false);
            $table->string('login_url', 255)->nullable(false);
            $table->string('logo_url', 255);
            $table->string('favicon_url', 255);
            $table->primary('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_url');
    }
};
