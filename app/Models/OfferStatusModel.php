<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferStatusModel extends Model
{
    use HasFactory;
    protected $table = "offers_status";
    protected $primaryKey = "id";
    public $timestamps = true;

    public static function getLapStatus($lenderName,$lenderStatus){
        $offerStatusModel = OfferStatusModel::where("lender_name",$lenderName)->where('lender_status',$lenderStatus)->where("is_active",1)->first();
        if(!empty($offerStatusModel)){
            return $offerStatusModel["lap_status"];
        }else{
            return "";
        }
    }
}
