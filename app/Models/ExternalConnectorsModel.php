<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalConnectorsModel extends Model
{
    use HasFactory;
    protected $table = "external_connectors";

    protected $primaryKey = "id";

    public $timestamps = true;

    public static function externalConnects($configKey){
        $connectData = ExternalConnectorsModel::where('config_key',$configKey)->first();
        if(!empty($connectData)){
            if($connectData['status'] == 1){
                return 1;
            }else{
                return 0;
            }
        }else{
            return response([
                'success' => 'false',
                'message' => 'Invalid Config Key'
            ], 400);
        }
        
    }
}
