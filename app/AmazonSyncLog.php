<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazonSyncLog extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
}
