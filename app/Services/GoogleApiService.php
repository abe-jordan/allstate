<?php

namespace App\Services;

class GoogleApiService
{

    public function __construct()
    {

    }
    
    public function get_maps_address($address){
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key=AIzaSyC1xTdwwV65v_zjYNi6OQq-zhwgPpVVPeY&sensor=true';
        //get response
        $contents = json_decode(file_get_contents($url),true);
        $components=[];
        //If $contents is not a boolean FALSE value.
        if($contents !== false){
            //Print out the contents.
            
           foreach ($contents['results'][0]['address_components'] as $k1 => $v1) {
                foreach ($v1['types'] as $k2 => $v2) {
                    $components[$v2]=$v1['long_name'];
                }
           }
        }
        return $components;
       }
}