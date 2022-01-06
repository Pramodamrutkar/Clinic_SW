<?php

namespace App\Http\Controllers;
use App\Exports\InternalReport;
use Illuminate\Http\Request;
use Excel;

class InternalReportExportController extends Controller
{
    
    public function export(){
        return Excel::download(new InternalReport,'creditappxlfile.xlsx');
    }

}
