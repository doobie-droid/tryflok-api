<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetResolutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_resolutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id');
            $table->string('url');
            $table->smallInteger('width')->nullable();
            $table->smallInteger('height')->nullable();
            $table->string('resolution')->nullable(); // 480, 720, 1080
            $table->string('storage_provider')->nullable();
            $table->string('storage_provider_id')->nullable();
            $table->string('encryption_key');
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
        Schema::dropIfExists('asset_resolutions');
    }
}
