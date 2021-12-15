<?php

namespace App\Http\Controllers;

use App\Models\SmartList;
use App\Models\SmartListData;
use App\Models\SmartListDataGroup;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use App\Models\Languages;

class SmartListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return SmartList::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'dgcode' => 'required',
            'lang_code' => 'required',
            'datacode' => 'required',
            'status'=> 'required' 
        ]);
        
        return SmartList::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function searchList(Request $request)
    {
        $lcode = trim($request->lang);
        $langData = Languages::where('langsdesc', $lcode )->first();

     
        $smartlistData = DB::select('SELECT a.status,a.dgcode,b.data_code,b.data_sdesc,b.data_ldesc,b.order,c.dg_desc FROM smart_lists as a 
        INNER JOIN smart_list_data as b 
        ON a.lang_code = b.lang_code AND a.datacode = b.data_code
        INNER JOIN smart_list_data_groups as c 
        on a.lang_code = c.lang_code AND a.dgcode = c.dg_code where a.lang_code ="'.$langData['l_code'].'" and a.status = 1 '  );
    
         //$data = $smartlistData;
         //dd($smartlistData);
        foreach($smartlistData as $key => $value)
        {
            $smartlistArray[$value->dg_desc][$value->order] = $value;
        }
       
        return $smartlistArray;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
