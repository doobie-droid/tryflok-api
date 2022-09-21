<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    public function up()
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->mediumText('description')->nullable();
            $table->foreignUuid('user_id'); // owner of collection
            $table->string('type'); // book, series, channel, digiverse
            $table->boolean('is_available')->default(0);
            $table->boolean('approved_by_admin')->default(0);
            $table->boolean('show_only_in_collections')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collections');
    }
}
