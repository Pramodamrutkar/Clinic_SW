<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasheAppModel extends Model
{
    use HasFactory;
    protected $table = "cashe_app";
    protected $primaryKey = "cashe_id";
    public $timestamps = true;
}
