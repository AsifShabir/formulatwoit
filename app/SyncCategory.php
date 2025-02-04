<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'store_name',
        'woocommerce_cat_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
