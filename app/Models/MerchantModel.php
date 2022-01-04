<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantModel extends Model
{
    use HasFactory;

    protected $table = "merchant";
    
    protected $primaryKey = "merchant_uid";

    public $incrementing = false;
    
    public $timestamps = false; 

}
