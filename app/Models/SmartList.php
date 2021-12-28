<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class SmartList extends Model
{
    use HasFactory;

    protected $fillable = [
        'dgcode',
        'lang_code',
        'datacode',
        'status'
    ];

    public static function getFieldDescription($id){
       $getDescription = DB::table('smart_lists')
        ->leftJoin('smart_list_data', 'smart_lists.datacode','=','smart_list_data.data_code')
        ->where('smart_lists.smart_list_code', $id)
        ->select('data_sdesc')
        ->first();
 
        if(empty($getDescription)){
            return Response([
                'status' => 'false',
                'message' => 'Incorrect Smartlist ID'
            ],400);
        }else{
            return $getDescription->data_sdesc;
        }
    }
}
