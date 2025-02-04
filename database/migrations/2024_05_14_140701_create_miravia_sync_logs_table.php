<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('miravia_sync_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id');
            $table->string('sync_type');
            $table->enum('operation_type', ['created', 'updated'])->nullable();
            $table->longText('data')->nullable();
            $table->longText('details')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('miravia_sync_logs');
    }
};
