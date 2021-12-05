<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBenefactorsTable extends Migration
{
    public function up()
    {
        Schema::create('benefactors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('benefactable');
            $table->foreignUuid('user_id');
            $table->decimal('share', 5, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('benefactors');
    }
}
