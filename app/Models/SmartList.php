<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Exception;
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
        try{
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
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        } catch (Exception $e) {
            abort(500, "Could not process a request");
        }
    }
}
