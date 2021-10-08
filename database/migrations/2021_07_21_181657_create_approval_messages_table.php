<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_id');
            $table->text('message');
            $table->string('from');//creator,admin
            $table->string('to');//creator,admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_messages');
    }
}
