<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentIssuesTable extends Migration
{
    public function up()
    {
        Schema::create('content_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_id');
            $table->string('title');
            $table->longText('description');
            $table->boolean('is_available')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_issues');
    }
}
