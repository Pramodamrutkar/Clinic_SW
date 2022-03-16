<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SmartList;

class OffersDataModel extends Model
{
    use HasFactory;

    protected $table = "offers_data";
    protected $primaryKey = "id";
    public $timestamps = true;

    public static function saveOffersData($offerData,$storeDataArray){
        foreach ($offerData as $key => $value) {
            $offerDataModel = new OffersDataModel();
            $offerDataModel->creditapp_uid = $storeDataArray['creditapp_uid'];
            $offerDataModel->email = $storeDataArray['email'];
            $offerDataModel->mobile_no = $storeDataArray['mobile_no'];
            $offerDataModel->postal_code = $storeDataArray['postal_code'];
            $offerDataModel->employment_type = SmartList::getFieldLongDescription($storeDataArray['employment_type']);
            $offerDataModel->age = $storeDataArray['age'];
            $offerDataModel->lender_name = $value["lender_name"];
            $offerDataModel->ranking = isset($value['offers'][0]["total_ranking_offer"]) ? $value['offers'][0]["total_ranking_offer"] : 0;
            $offerDataModel->offer_amount = $value['offers'][0]["offer_amount"];
            $offerDataModel->offer_roi = $value['offers'][0]["offer_roi"];
            $offerDataModel->offer_pf = $value['offers'][0]["offer_pf"];
            $offerDataModel->offer_tenure = $value['offers'][0]["offer_tenure"];
            $offerDataModel->save();
        }
    }


}
