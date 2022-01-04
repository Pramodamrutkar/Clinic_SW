<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantContactModel extends Model
{
    use HasFactory;

    protected $table = "merchant_contact";
    
    protected $primaryKey = "merchant_contact_uid";
    
    public $incrementing = false;
    
    public $timestamps = false; 

}
