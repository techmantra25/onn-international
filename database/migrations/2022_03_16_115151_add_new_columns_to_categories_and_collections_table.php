<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToCategoriesAndCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('parent')->nullable()->after('name');
            $table->string('sketch_icon')->nullable()->after('icon_path');
        });
        Schema::table('collections', function (Blueprint $table) {
            $table->string('sketch_icon')->nullable()->after('icon_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('parent');
            $table->dropColumn('sketch_icon');
        });
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('sketch_icon');
        });
    }
}
