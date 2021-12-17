<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferWeightage extends Model
{
    use HasFactory;
	
	protected $table = "offer_weightage";
    
    protected $primaryKey = "id";
    
    public $timestamps = false; 
}
