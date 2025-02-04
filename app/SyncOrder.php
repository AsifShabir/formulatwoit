<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'store_name',
        'woocommerce_cat_id',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
