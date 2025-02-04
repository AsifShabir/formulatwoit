<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_name',
        'woocommerce_product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function scopeForLocation($query, $location_id)
    {
        return $query->whereHas('product.product_locations', function ($query) use ($location_id) {
            $query->where('product_locations.location_id', $location_id);
        });
    }
}
