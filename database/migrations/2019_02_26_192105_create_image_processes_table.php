<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_processes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('image_hash_name');
            $table->string('original_image_path');
            $table->string('modified_image_path');
            $table->string('filter_name')->nullable();
            $table->string('watermark_text')->nullable();
            $table->string('watermark_image_hash_name')->nullable();
            $table->string('watermark_image_path')->nullable();
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
        Schema::dropIfExists('image_processes');
    }
}
