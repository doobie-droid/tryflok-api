<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSalesTableToRevenuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('sales', 'revenues');

        Schema::table('revenues', function (Blueprint $table) {
            $table->renameColumn('saleable_type', 'revenueable_type');
            $table->renameColumn('saleable_id', 'revenueable_id');
            $table->string('revenue_from')->default('sale'); // sale, referall, tip
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revenues', function (Blueprint $table) {
            //
        });
    }
}
