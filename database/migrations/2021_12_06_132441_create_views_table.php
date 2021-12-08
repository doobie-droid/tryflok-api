<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewsTable extends Migration
{
    public function up()
    {
        Schema::create('views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable();
            $table->uuidMorphs('viewable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('views');
    }
}
