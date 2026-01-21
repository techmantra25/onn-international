<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('size_chart')->nullable()->after('offer_price');
            $table->string('pack', 50)->nullable()->after('size_chart');
            $table->string('master_pack', 50)->nullable()->after('pack');
            $table->string('only_for')->default('all')->comment('men, women, kids, all')->after('master_pack');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size_chart');
            $table->dropColumn('pack');
            $table->dropColumn('master_pack');
            $table->dropColumn('only_for');
        });
    }
}
