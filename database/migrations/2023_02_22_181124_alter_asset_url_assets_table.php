<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('assets')
            ->where('asset_url', '=', '{{checkout_url}}/assets/experience/one_page.js')
            ->update(['asset_url' => '{{assets_url}}/one_page.js']);
        DB::table('assets')
            ->where('asset_url', '=','{{checkout_url}}/assets/experience/three_page.js')
            ->update(['asset_url' => '{{assets_url}}/three_page.js']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('assets')
            ->where('asset_url', '=', '{{assets_url}}/one_page.js')
            ->update(['asset_url' => '{{checkout_url}}/assets/experience/one_page.js']);
        DB::table('assets')
            ->where('asset_url', '=','{{assets_url}}/three_page.js')
            ->update(['asset_url' => '{{checkout_url}}/assets/experience/three_page.js']);
    }
};
