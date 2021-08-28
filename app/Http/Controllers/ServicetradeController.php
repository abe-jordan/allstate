<?php

namespace App\Http\Controllers;
use App\Services\ServiceTradeService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Response;
class ServicetradeController extends Controller
{
    //
    protected $st;
    public function __construct(ServiceTradeService $serviceTrade)
    { 

        //declare ServiceTrade Service for use
        $this->st = $serviceTrade;
     
    }

    public function receive()
    {
        $payload = array();
        $hook = Input::all();
        //receive webhook data
        $store_items = array('invoice','job','quote');
        foreach ($hook['data'] as $data){
            
            if(in_array($data['entity']['type'],$store_items))
            {
                //Log::debug($data);
                array_push($payload, array('action' => $data['action'],'timestamp' => $data['timestamp'], 'type' => $data['entity']['type'], 'id' => $data['entity']['id']));
            }
            }
            if(is_null($payload) || !$payload){
                return response('No approved items receieved.',200);
            }
        $receive = $this->st->receive($payload);
        return $receive;
    }

    public function send($method = '', $endpoint = '', $body = '')
    {
        $body = '';
        $payload = [
            'method' => 'GET',
            'endpoint' => 'auth',
            'body' => $body
        ];
        $res = $this->st->send($payload);
        $stream = $res->getStatusCode();
        if($stream == 200)
        {
        return response(NULL,200);
        }else{
            return response(NULL,500);
        }
        //$contents = json_decode($stream->getContents()); // returns all the contents
        
    }
public function unscheduled()
{
    $app_payload['method'] = 'GET';
    $createdAfter = strtotime('1 January 2018');
    $app_payload['endpoint'] = 'appointment?status=unscheduled&createdAfter='.$createdAfter.'';
    $appointments = json_decode($this->st->send($app_payload)->getBody()->getContents());
    $quotes = array();
    $filename = date('Y',$createdAfter)."_unscheduled_quoted_jobs.csv";
    $handle = fopen($filename, 'w+');
    fputcsv($handle, array('quote.id','quote.status','quote.price','appointment.id', 'appointment.status','appointment.created', 'address.street','address.city','job.id','job.number','job.name'));
    foreach ($appointments->data->appointments as $app_key => $app_value) {
        array_push($quotes,$app_value->job->id);
    }
    $appointment_list = trim(json_encode($quotes), '[]');
    $quotes_list = $this->getQuotes($appointment_list);
    foreach ($appointments->data->appointments as $app_key => $app_value) {
        if(isset($quotes_list[$app_value->job->id])){
                $quotes_list[$app_value->job->id]["appointment.id"] = $app_value->id;//app.id
                $quotes_list[$app_value->job->id]["appointment.status"] = $app_value->status;//app.status
                $quotes_list[$app_value->job->id]["appointment.created"] = date('m/d/Y',$app_value->created);//app.created
                $quotes_list[$app_value->job->id]["address.street"] = $app_value->location->address->street;//address.street
                $quotes_list[$app_value->job->id]["address.city"] = $app_value->location->address->city;//address.city
                $quotes_list[$app_value->job->id]["job.id"] = $app_value->job->id;//job.id
                $quotes_list[$app_value->job->id]["job.number"] = $app_value->job->number;//job.number
                $quotes_list[$app_value->job->id]["job.name"] = $app_value->job->name;//job.name
                fputcsv($handle, $quotes_list[$app_value->job->id]);
        }
    }
        fclose($handle);
        $headers = array(
            'Content-Type' => 'text/csv',
        );
        return Response::download($filename, $filename, $headers)->deleteFileAfterSend(true);
}
 public function getQuotes($appointments)
{
    $quote_payload['method'] = 'GET';
    $quote_payload['endpoint'] = 'quote?jobId='.$appointments;
    $quote = json_decode($this->st->send($quote_payload)->getBody()->getContents());
    // return $quote;
    $quotes_array = array();
    foreach ($quote->data->quotes as $key => $value) {
       $quotes_array[$value->jobs[0]->id] = array(
        "quote.id" => $value->id,
        "quote.status" => $value->status,
        "quote.price" => $value->totalPrice
       );
    }
    return $quotes_array;
} 
}
