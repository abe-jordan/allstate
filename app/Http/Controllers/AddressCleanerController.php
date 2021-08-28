<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Services\GoogleApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class AddressCleanerController extends Controller
{
    //Reads and Cleans addresses using Google api

    public function __construct()
    {

    }

    /**
     * Show 
     */
    public function index()
    {
        return view('addressCleaner');
    }

    /**
     * Create new clean address
     * @param array $address
     * @return array $cleanAddress
     */
    public function create(request $request)
    {
        $locations = json_decode($request->locations,true);
        #return $locations[0];
        $header_array = array();
        $header;
        foreach ($locations[0] as $key => $value) {
            //set headers
            
            if(is_array($value)){
                foreach ($value as $i => $nest_value) {
                    array_push($header_array,$key.'/'.$i);
                } 
            }else{
                array_push($header_array,$key);
            }
            $implode = implode(",", $header_array);
            $implode.PHP_EOL;
        }
        $header = $implode;
        //Read Through Contents

        $content =array();
        foreach ($locations as $l_key => $location) {
            $body = array();
            foreach ($location as $key => $value) {
            if(is_array($value)){
                foreach ($value as $i => $nest_value) {
                    array_push($body,$nest_value);
                } 
            }else{
                array_push($body,$value);
            }
        }
        //array_push($content,$header_array);
        array_push($content,$body);
        }
        $filename = "FormattedAddresses_".time().".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, $header_array);
    
        foreach($content as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    
        $headers = array(
            'Content-Type' => '.csv',
        );
        return $filename;
    }

    /**
     * Reads in the list of locations from a csv file
     * 
     * @param string $filePath
     * @return array $addressList
     */
    public function read(request $request)
    {
        if($request->hasFile('address_file')){
            $file = $request->file('address_file')->path();
        }else{
            return array(
                        "error"=>array(
                            "response"=>"No File Uploaded, Scotty.",
                            "request"=>$request
                            )
                        );
        }
        //get file
        $csv = array_map('str_getcsv', file($file));
        array_walk($csv, function(&$a) use ($csv) {
          $a = array_combine(mb_convert_encoding($csv[0], 'UTF-8', 'UTF-8'),mb_convert_encoding($a, 'UTF-8', 'UTF-8'));
        });
        array_shift($csv); # remove column header
        return $csv;
    }

    public function update(request $request)
    {
/*         $google_address = get_maps_address(((array_key_exists('street',$loc_value)) ? $loc_value['street'].' ' : '').
        ((array_key_exists('city',$loc_value))?$loc_value['city'].' ' :'').
        ((array_key_exists('state',$loc_value))? $loc_value['state'].' ' :'').
        ((array_key_exists('postalcode',$loc_value))? $loc_value['postalcode'].' ':'')
        ); */
        $google = new GoogleApiService();
        $google_address = $google->get_maps_address($request->street.' '.$request->city.' '.$request->state.' '.$request->postalcode);

        if(array_key_exists('locality',$google_address)){
            $city =  $google_address['locality'];
        }elseif(array_key_exists('sublocality',$google_address)){
            $city =  $google_address['sublocality'];
        }else{
            $city =  null;
        }
        $address_array = array(
            "street" => $google_address['street_number']." ".$google_address['route'],
            "city" => $city,
            "state" => $google_address['administrative_area_level_1'],
            "zip" => $google_address['postal_code']
        );
        return $address_array;
    }

    public function delete()
    {

    }
}
