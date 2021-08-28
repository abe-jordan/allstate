<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceTradeService
{
    protected $client;
    protected $token;
    protected $base_header;
    protected $store_items = array(
        'company' => 'companies',
        'invoice' => 'invoices',
        'job' => 'jobs',
        'location' => 'locations',
    );
    protected $data_array = array(
        'Job' => array(
            'id' => 'id',
            'number' => 'number',
            'status' => 'status',
            'description' => 'description',
        ),
        'Invoice' => array(
            'id' => 'id',
            'price' => 'totalPrice',
            'status' => 'status',
            'updated' => 'updated',
            'rec' => 'id',
        ),
        'Quote' => array(

        ),
    );
    public function __construct()
    {
        $this->client = new Client(
            [
                'cookies' => true,
                'base_uri' => 'https://api.servicetrade.com/api/',
            ]
        );
        //check if cache data exist
        if (!$this->check_token()) {
            $this->generate_token();
        }
        //check if token is valid
        $this->token = Cache::get('st_token');
        if ((bool) !$this->validate_token()) {
            $this->generate_token();
        }
        //continue with request
        $this->base_header = [
            'content-type' => 'application/json',
            'Cookie' => 'PHPSESSID=' . $this->token,
            'timeout' => 180,
        ];
    }
    public function receive($request)
    {
        if (!$request) {
            return false;
        }

        $response = array();
        
        foreach ($request as $item) {

            switch ($item['type']) {
                case 'job':
                
                    $transmit = array('type' => $item['type'], 'id' => $item['id'], 'action' => $item['action']);
                    $req = $this->job($transmit);
                    break;
                case 'invoice':
                
                    $transmit = array('type' => $item['type'], 'id' => $item['id'], 'action' => $item['action']);
                    $req = $this->invoice($transmit);
                    break;
                case 'quote':
                
                    $transmit = array('type' => $item['type'], 'id' => $item['id'], 'action' => $item['action']);
                    $req = $this->quote($transmit);
                    break;
                default:
                    return array_push($response, array('status'=>'failure', 'message' => 'No handler for type: '.$item['type']));
                    break;
            }
            
            array_push($response, $req);
        }
        return $response;
    }
    /**
     * Send request to external api
     * @var array
     * @return response
     *
     */
    public function send($payload)
    {
        if (isset($payload['body'])) {
            $this->base_header['body'] = $payload['body'];
        }
        try {
            $res = $this->client->request($payload['method'], $payload['endpoint'], [
                'headers' => $this->base_header,

            ]
            );
            $stream = $res->getStatusCode();
            if($stream == 200)
            {
                return $res;
            }else{
                Log::debug('Error: ' . $stream);
            }
            //return true;
        } catch (Exception $e) {
            Log::debug('Error: ' . $e);
            return $res->getStatusCode();

        }

    }
    /**
     * Collect id, number, status, description
     */
    public function job($transmit)
    {
        $payload = [
            'method' => 'GET',
            'endpoint' => 'job/' . $transmit['id'],
            'body' => '',
        ];
        $req = $this->send($payload);
        $stream = $req->getBody();
        $contents = json_decode($stream->getContents()); // returns all the contents
        $str = preg_replace("/[^A-Za-z0-9 . , ? &]/", " ", strtoupper($contents->data->description));
        preg_match_all('/\b(REC|RE)\s*(\d+)\b/', $str, $matches);
        $description = $str;
        $data_items = array(
            'type' => $transmit['type'],
            'data' => array(
                'id' => $contents->data->id,
                'number' => $contents->data->number,
                'status' => $contents->data->status,
                'description' => $description,
            ),

        );
        $store = $this->update(array($data_items));
        return $store;

    }

    public function invoice($transmit)
    {
        $data_items = array();
        //Request invoice data
        $payload = [
            'method' => 'GET',
            'endpoint' => 'invoice/' . $transmit['id'],
            'body' => '',
        ];
        $req = $this->send($payload);
        $stream = $req->getBody();
        $contents = json_decode($stream->getContents()); // returns all the contents
        //Build Invoice data array
        $invoice = array(
            'type' => 'invoice',
            'data' => array(
                'id' => $contents->data->id,
                'price' => preg_replace('/,/', '',$contents->data->totalPrice),
                'status' => $contents->data->status,
                'updated' => $contents->data->updated,
                'rec' => ($contents->data->job->id ? $this->extractREC($contents->data->job->id) : ''),
            ),
        );
        array_push($data_items, $invoice);
        //Build Job data
        if ($contents->data->job->id) {
            $job_exist = DB::table('jobs')->where('id', '=', $contents->data->job->id)->first();
            if (!$job_exist) {
                $this->job(array('type' => 'job', 'id' => $contents->data->job->id));
            }
            $job = array(
                'type' => 'job',
                'data' => array(
                    'id' => $contents->data->job->id,
                    'invoice_id' => $contents->data->id,
                ),

            );
            array_push($data_items, $job);
        }
        $invoice_items = array('type' => 'invoiceItem', 'data' => array());
        foreach ($contents->data->items as $item_label => $item_value) {
            # code...
            array_push($invoice_items['data'], array(
                'id' => $item_value->id,
                'invoice_id' => $contents->data->id,
                'price' => preg_replace('/,/', '',$item_value->totalPrice),
                'quantity' => $item_value->quantity,
                'description' => $item_value->description,
            )
            );
        }
        array_push($data_items, $invoice_items);
        return $this->update($data_items);
    }
    
    /**
     * Extracts REC from either job description, quote notes, or a string
     *
     * @return int
     */
    public function extractREC($job_id = '', $quote_id = '', $text_string = '')
    {

        if ($job_id) {
            $request = array('endpoint' => 'job', 'id' => $job_id);
        } elseif ($quote_id) {
            $request = array('endpoint' => 'quote', 'id' => $quote_id);
        }
        if (isset($request)) {
            $payload = [
                'method' => 'GET',
                'endpoint' => $request['endpoint'] . '/' . $request['id'],
                'body' => '',
            ];
            $req = $this->send($payload);
            $stream = $req->getBody();
            $contents = json_decode($stream->getContents()); // returns all the contents
            switch ($request['endpoint']) {
                case 'job':
                    $text_string = $contents->data->description;
                    break;

                case 'quote':
                    $text_string = $contents->data->notes;
                    break;

                default:
                    # code...
                    break;
            }
        }
        $re = '/\b(REC|RE)\s*(\d+)\b/';
        //set the incoming string to replace all characters that are not from A-Z or 0-9 with a space
        $str = preg_replace("/[^A-Za-z0-9 ]/", " ", strtoupper($text_string));
        //get all matches: i.e. REC, REC 1111, 1111
        preg_match_all($re, $str, $matches);
        //returned as array, get only the number
        try{
            $rec_num = $matches[2][0];
        }catch(\Exception $e){
            $rec_num = 0;
        }
        
        return $rec_num;

    }

    /**
     * Quote method
     *
     */
    public function quote($transmit)
    {
        
    $data_items = array();
        //Request invoice data
        $payload = [
            'method' => 'GET',
            'endpoint' => 'quote/'.$transmit['id'],
            'body' => '',
        ];
        $req = $this->send($payload);
        $stream = $req->getBody();
        $contents = json_decode($stream->getContents()); // returns all the contents
        //Build Quote data array
        $quote = array(
            'type' => 'quote',
            'data' => array(
                'id' => $contents->data->id,
                'rec' => ($contents->data->id ? $this->extractREC(NULL,$contents->data->id) : ''),
                'price' => preg_replace('/,/', '',$contents->data->totalPrice),
                'status' => $contents->data->status,
                'updated' => $contents->data->updated,
                
            ),
        );
        array_push($data_items, $quote);
        //Build Job data
        foreach ($contents->data->jobs as $job_item) {
            # code...
            if ($job_item->id) {
                $job_exist = DB::table('jobs')->where('id', '=', $job_item->id)->first();
                if (!$job_exist) {
                    $this->job(array('type' => 'job', 'id' => $job_item->id));
                }
                $job = array(
                    'type' => 'job',
                    'data' => array(
                        'id' => $job_item->id,
                        'quote_id' => $contents->data->id,
                    ),
    
                );
                array_push($data_items, $job);
            }
        }

        return $this->update($data_items);
    }
    /**
     * Update existing record in database
     * return results
     * @return array
     */
    private function update($payload)
    {
        $results = array();
        foreach ($payload as $item) {
            # code...
            $proper_model = (string) ucwords($item['type']);
            $app = 'App\\' . $proper_model;
            if (count($item['data']) !== count($item['data'], COUNT_RECURSIVE)) {
                foreach ($item['data'] as $multi_item) {
                    $store = $app::updateOrCreate(
                        [
                            'id' => $multi_item['id'],
                        ],
                        $multi_item
                    );
                    if ($store) {
                        array_push($results, array('status' => 'success','type'=> $item['type'],'data' => $store));
                    } else {
                        array_push($results, array('status' => 'fail', 'message' => 'Could not successfully processed data received. Check logs for more details.'));
                    }
                }
            } else {
                $store = $app::updateOrCreate(
                    [
                        'id' => $item['data']['id'],
                    ],
                    $item['data']
                );
                if ($store) {
                    array_push($results, array('status' => 'success','type'=> $item['type'], 'data' => $store));
                } else {
                    array_push($results, array('status' => 'fail', 'message' => 'Could not successfully processed data received. Check logs for more details.'));
                }

            }
        }

        return $results;
    }
    /**
     * Create new record in database
     * return results
     * return array
     */
    private function create($payload)
    {

        return array('status' => 'success', 'message' => 'Hello, I received the create request for table: ' . $payload['table'] . '');
    }

    private function check_token()
    {
        if (Cache::has('st_token')) {
            return true;
        }
        return false;
    }
    public function validate_token()
    {
        try {
            $res = $this->client->request('GET', 'auth', [
                'headers' => $this->base_header,
            ]
            );
            return true;
        } catch (ClientException $e) {
            return false;
            Log::debug('Error: ');
        }
        //return false;
    }
    private function generate_token()
    {
        //make call to servicetrade
        //get token
        //cache token
        //return true when token is stored
        try {
            $res = $this->client->request('POST', 'auth', array(
                'content-type' => 'application/json',
                'form_params' => array(
                    'username' => 'Sheldon',
                    'password' => 'AllstateSprinkler1',
                ),
            )
            );
        } catch (Exception $e) {
            return false;
            Log::debug('Error: ' . $e);
        }
        if ($res->getStatusCode() == 200) {
            //Successful auth. Cache returned token
            $stream = $res->getBody();
            $contents = json_decode($stream->getContents()); // returns all the contents
            //$body = $res->getBody()->getContents();
            Cache::forever('st_token', $contents->data->authToken);
            //dd($contents->data->authToken);
        } else {
            return false;
            Log::debug('Error: ' . $res->getStatusCode());
        }
        return true;
    }
}
