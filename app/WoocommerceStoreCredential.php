<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoocommerceStoreCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name', 'app_url', 'business_id', 'consumer_key', 'consumer_secret', 'location_id', 'default_tax_class', 
        'product_tax_type', 'default_selling_price_group', 'sync_description_as', 'product_fields_for_create', 
        'manage_stock_for_create', 'in_stock_for_create', 'product_fields_for_update', 'manage_stock_for_update', 
        'in_stock_for_update', 'order_statuses', 'shipping_statuses', 'wh_oc_secret', 'wh_ou_secret', 
        'wh_od_secret', 'wh_or_secret', 'enable_auto_sync'
    ];

    protected $casts = [
        'enable_auto_sync' => 'boolean'
    ];
}
