<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiraviaProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'miravia_id',
        'sku_id',
        'type',
        'seller_sku',
        'name',
        'category',
        'brand',
        'price',
        'quantity',
    ];
}
