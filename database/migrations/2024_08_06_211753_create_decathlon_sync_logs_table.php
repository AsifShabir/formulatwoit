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
        /*Schema::create('decathlon_sync_logs', function (Blueprint $table) {
           $table->id();
            $table->integer('business_id');
            $table->string('sync_type');
            $table->enum('operation_type', ['created', 'updated'])->nullable();
            $table->longText('data')->nullable();
            $table->longText('details')->nullable();
            $table->integer('created_by');
            $table->timestamps();
        });

        Schema::create('decathlon_products', function (Blueprint $table) {
            $table->id();
            $table->string('productName');
            $table->string('asin');
            $table->string('fnSku');
            $table->string('parent_id')->nullable();
            $table->string('sellerSku');
            $table->string('condition');
            $table->string('totalQuantity');
            $table->timestamps();
        });


        Schema::table('business', function (Blueprint $table) {
            $table->text('decathlon_api_settings')->nullable()->after('pos_settings');
        });*/


        // Schema::table('categories', function (Blueprint $table) {
        //     $table->integer('decathlon_cat_id')->nullable()->after('created_by');
        // });

        // Schema::table('products', function (Blueprint $table) {
        //     $table->string('decathlon_product_id')->nullable()->after('created_by');
        //     $table->boolean('decathlon_disable_sync')->default(0)->after('decathlon_product_id');
        //     $table->integer('decathlon_media_id')->nullable()->after('image');
        // });

        // Schema::table('transactions', function (Blueprint $table) {
        //     $table->integer('decathlon_order_id')->nullable()->after('created_by');
        // });

        // Schema::table('transaction_sell_lines', function (Blueprint $table) {
        //     $table->integer('decathlon_line_items_id')->nullable()->after('sell_line_note');
        // });

        // Schema::table('tax_rates', function (Blueprint $table) {
        //     $table->integer('decathlon_tax_rate_id')->nullable()->after('created_by');
        // });

        // Schema::table('variation_templates', function (Blueprint $table) {
        //     $table->integer('decathlon_attr_id')->nullable()->after('business_id');
        // });

        // Schema::table('variations', function (Blueprint $table) {
        //     $table->string('decathlon_variation_id')->nullable()->after('product_variation_id');
        // });

        // Schema::table('media', function (Blueprint $table) {
        //     $table->integer('decathlon_media_id')->nullable()->after('model_type');
        // });

        // Schema::table('business', function (Blueprint $table) {
        //     $table->text('decathlon_skipped_orders')->nullable()->after('decathlon_api_settings');
        // });


        // Indexing
        // Schema::table('categories', function (Blueprint $table) {
        //     $table->index('decathlon_cat_id');
        // });

        Schema::table('products', function (Blueprint $table) {
            $table->index('decathlon_product_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('decathlon_order_id');
        });

        // Schema::table('transaction_sell_lines', function (Blueprint $table) {
        //     $table->index('decathlon_line_items_id');
        // });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->index('decathlon_tax_rate_id');
        });

        Schema::table('variation_templates', function (Blueprint $table) {
            $table->index('decathlon_attr_id');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->index('decathlon_variation_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index('decathlon_media_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index('decathlon_media_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('decathlon_sync_logs');
        Schema::dropIfExists('decathlon_products');
    }
};
