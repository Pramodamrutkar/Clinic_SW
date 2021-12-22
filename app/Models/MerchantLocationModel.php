<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantLocationModel extends Model
{
    use HasFactory;
    protected $table = "merchant_location";
    
    protected $primaryKey = "merchant_location_id";
    
    public $timestamps = false; 
}
