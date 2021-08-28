<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DataToFileService;
class FileBuilderController extends Controller
{
    //
    
    public function __construct(DataToFileService $export)
    {
        $this->file = $export;
    }

    public function build()
    {
        //get date range
        //get data requested
        //send info to builder to build csv
        //retrieve data save as csv
        
        
    }
}
