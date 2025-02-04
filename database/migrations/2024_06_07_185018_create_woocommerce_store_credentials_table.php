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
        Schema::create('woocommerce_store_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_url');
            $table->string('consumer_key');
            $table->string('consumer_secret');
            $table->integer('business_id');
            $table->string('location_id')->nullable();
            $table->string('default_tax_class')->nullable();
            $table->string('product_tax_type')->nullable();
            $table->string('default_selling_price_group')->nullable();
            $table->string('sync_description_as')->nullable();
            $table->string('product_fields_for_create')->nullable();
            $table->string('manage_stock_for_create')->nullable();
            $table->string('in_stock_for_create')->nullable();
            $table->string('product_fields_for_update')->nullable();
            $table->string('manage_stock_for_update')->nullable();
            $table->string('in_stock_for_update')->nullable();
            $table->string('order_statuses')->nullable();
            $table->string('shipping_statuses')->nullable();
            $table->string('wh_oc_secret')->nullable();
            $table->string('wh_ou_secret')->nullable();
            $table->string('wh_od_secret')->nullable();
            $table->string('wh_or_secret')->nullable();
            $table->boolean('enable_auto_sync')->default(false);
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
        Schema::dropIfExists('woocommerce_store_credentials');
    }
};
