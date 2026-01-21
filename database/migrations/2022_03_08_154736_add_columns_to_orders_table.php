<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_no')->after('id');
            $table->tinyInteger('shippingSameAsBilling')->default(0)->after('billing_pin');
            $table->double('shipping_charges', 10, 2)->default(0.00)->after('amount');
            $table->string('shipping_method', 100)->default('standard')->after('shipping_charges');
            $table->string('payment_method', 100)->default('cash_on_delivery')->after('gst_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_no');
            $table->dropColumn('shippingSameAsBilling');
            $table->dropColumn('shipping_charges');
            $table->dropColumn('shipping_method');
            $table->dropColumn('payment_method');
        });
    }
}
