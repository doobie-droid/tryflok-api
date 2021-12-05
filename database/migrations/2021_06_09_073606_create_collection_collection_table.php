<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionCollectionTable extends Migration
{
    public function up()
    {
        Schema::create('collection_collection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id');
            $table->foreignUuid('child_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_collection');
    }
}
