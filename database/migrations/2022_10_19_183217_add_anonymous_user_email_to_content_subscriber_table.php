<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnonymousUserEmailToContentSubscriberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('content_subscriber', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->change();
            $table->string('anonymous_user_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content_subscriber', function (Blueprint $table) {
            //
        });
    }
}
