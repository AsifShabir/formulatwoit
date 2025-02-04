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
            $table->integer('miravia_cat_id')->nullable()->after('created_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('miravia_product_id')->nullable()->after('created_by');
            $table->boolean('miravia_disable_sync')->default(0)->after('miravia_product_id');
            $table->integer('miravia_media_id')->nullable()->after('image');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('miravia_order_id')->nullable()->after('created_by');
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->string('miravia_line_items_id')->nullable()->after('sell_line_note');
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->integer('miravia_tax_rate_id')->nullable()->after('created_by');
        });

        Schema::table('variation_templates', function (Blueprint $table) {
            $table->integer('miravia_attr_id')->nullable()->after('business_id');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->string('miravia_variation_id')->nullable()->after('product_variation_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->integer('miravia_media_id')->nullable()->after('model_type');
        });

        Schema::table('business', function (Blueprint $table) {
            $table->text('miravia_skipped_orders')->nullable()->after('miravia_api_settings');
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
