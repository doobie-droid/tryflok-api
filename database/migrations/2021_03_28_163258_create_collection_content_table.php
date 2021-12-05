<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionContentTable extends Migration
{
    public function up()
    {
        Schema::create('collection_content', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id');
            $table->foreignUuid('content_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_content');
    }
}
