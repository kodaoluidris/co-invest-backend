<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainPropertyGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('main_property_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_property_id')->constrained('main_properties')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('no_of_people');
            $table->integer('group_price');
            $table->integer('groups')->nullable();
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
        Schema::dropIfExists('main_property_groups');
    }
}
