<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('main_properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('image')->nullable();
            $table->json('filename')->nullable();
            $table->foreignId('property_type_id')->constrained('property_types');
            $table->text('description');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('price');
            $table->integer('groups');
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
        Schema::dropIfExists('main_properties');
    }
}
