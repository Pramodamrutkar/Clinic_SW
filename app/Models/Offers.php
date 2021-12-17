<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offers extends Model
{
    use HasFactory;
	

    protected $table = "offers";
    
    protected $primaryKey = "offer_id";
    
    public $timestamps = false; 
}
