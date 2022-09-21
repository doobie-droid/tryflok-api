<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserablesTable extends Migration
{
    public function up()
    {
        Schema::create('userables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id');//person that owns entities
            $table->foreignUuid('parent_id')->nullable();//references id on userables
            $table->uuidMorphs('userable'); // the entitiy (digiverse, collection, or content)
            $table->string('status')->default('available'); // wishlist, available, subscription-ended, content-deleted
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('userables');
    }
}
