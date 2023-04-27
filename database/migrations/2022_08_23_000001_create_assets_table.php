<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name', 64)->nullable(false);
            $table->string('asset_url', 255)->nullable(false);
            $table->string('flow_id', 64)->nullable(false);
            $table->tinyInteger('position')->default(1)->nullable(false);
            $table->string('asset_type', 10)->nullable(false);
            $table->tinyInteger('is_asynchronous')->default(0)->nullable(false);
            $table->integer('parent_id')->default(0)->nullable(false);
        });

        DB::table('assets')->insert(
            [
                'asset_name' => '3 Pages template',
                'asset_url' => '{{checkout_url}}/assets/experience/three_page.js',
                'flow_id' => 'Bold 3 pages',
                'asset_type' => 'js',
            ]
        );
        DB::table('assets')->insert([
                'asset_name' => '1 Page template',
                'asset_url' => '{{checkout_url}}/assets/experience/one_page.js',
                'flow_id' => 'Bold 1 page',
                'asset_type' => 'js',
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};
