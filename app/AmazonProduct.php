<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'asin',
        'productName',
        'fnSku',
        'sellerSku',
        'condition',
        'totalQuantity',
        'parent_id',
    ];
}
