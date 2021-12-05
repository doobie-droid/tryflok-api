<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaggableTable extends Migration
{
    public function up()
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tag_id');
            $table->uuidMorphs('taggable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('taggable');
    }
}
