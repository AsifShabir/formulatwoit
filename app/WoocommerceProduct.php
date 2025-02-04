<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoocommerceProduct extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'woo_id', 'name', 'store_id', 'variations', 'category', 'type', 'regular_price', 'sale_price', 'sku', 'stock_quantity'
    ];
}
