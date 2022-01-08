<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->renameColumn('user_id', 'recipient_id')->default(0);
            $table->foreignUuid('notifier_id');
        });
    }

    public function down()
    {
        //
    }
}
