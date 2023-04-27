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
        Schema::create('shop_assets', function (Blueprint $table) {
            $table->unsignedInteger('shop_id', false)->nullable(false);
            $table->unsignedInteger('asset_id', false)->nullable(false);
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
        Schema::dropIfExists('shop_assets');
    }
};
