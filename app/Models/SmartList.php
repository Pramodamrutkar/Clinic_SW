<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartList extends Model
{
    use HasFactory;

    protected $fillable = [
        'dgcode',
        'lang_code',
        'datacode',
        'status'
    ];
}
