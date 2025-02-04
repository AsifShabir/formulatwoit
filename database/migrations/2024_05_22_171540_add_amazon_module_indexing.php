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
            $table->index('amazon_cat_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('amazon_product_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('amazon_order_id');
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->index('amazon_line_items_id');
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->index('amazon_tax_rate_id');
        });

        Schema::table('variation_templates', function (Blueprint $table) {
            $table->index('amazon_attr_id');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->index('amazon_variation_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index('amazon_media_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index('amazon_media_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
