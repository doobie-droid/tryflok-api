<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategorablesTable extends Migration
{
    public function up()
    {
        Schema::create('categorables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id');
            $table->uuidMorphs('categorable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categorables');
    }
}
