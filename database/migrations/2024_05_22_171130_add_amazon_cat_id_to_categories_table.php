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
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('amazon_cat_id')->nullable()->after('created_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('amazon_product_id')->nullable()->after('created_by');
            $table->boolean('amazon_disable_sync')->default(0)->after('amazon_product_id');
            $table->integer('amazon_media_id')->nullable()->after('image');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('amazon_order_id')->nullable()->after('created_by');
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->integer('amazon_line_items_id')->nullable()->after('sell_line_note');
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->integer('amazon_tax_rate_id')->nullable()->after('created_by');
        });

        Schema::table('variation_templates', function (Blueprint $table) {
            $table->integer('amazon_attr_id')->nullable()->after('business_id');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->string('amazon_variation_id')->nullable()->after('product_variation_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->integer('amazon_media_id')->nullable()->after('model_type');
        });

        Schema::table('business', function (Blueprint $table) {
            $table->text('amazon_skipped_orders')->nullable()->after('amazon_api_settings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
};
