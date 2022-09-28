<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetablesTable extends Migration
{
    public function up()
    {
        Schema::create('assetables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('purpose')->comment('cover, page, i360-page, video, profile-picture');
            $table->foreignUuid('asset_id');
            $table->uuidMorphs('assetable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assetables');
    }
}
