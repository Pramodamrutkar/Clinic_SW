<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationTypesModel extends Model
{
    use HasFactory;
    protected $table = "comm_types";
    protected $primaryKey = "comm_id";
    public $timestamps = true;

    public function getCommunicationType(){
        $data = CommunicationTypesModel::all();
        return $data;
    }
}
