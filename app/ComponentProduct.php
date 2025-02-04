<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ComponentProduct extends Pivot
{
    // You can add custom behavior here if needed

    // If the pivot table contains additional fields, you can specify them as fillable
    protected $fillable = [
        'product_id',
        'component_id',
        // Add any other fields you have in the pivot table, e.g. 'quantity'
    ];
}
