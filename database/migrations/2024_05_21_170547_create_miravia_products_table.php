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
        Schema::create('miravia_products', function (Blueprint $table) {
            $table->id();
            $table->string('miravia_id')->nullable();
            $table->integer('business_id')->nullable();
            $table->enum('type', ['single', 'variable'])->nullable();
            $table->string('sku_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('name')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
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
        Schema::dropIfExists('miravia_products');
    }
};
