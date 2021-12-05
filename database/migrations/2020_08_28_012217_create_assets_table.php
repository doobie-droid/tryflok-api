<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('url');
            $table->string('storage_provider')->nullable();
            $table->string('storage_provider_id')->nullable();
            $table->string('asset_type')->comment('image, video, pdf, text, audio');
            $table->string('mime_type');
            $table->string('encryption_key')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resources');
    }
}
