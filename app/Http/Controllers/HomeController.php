<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use App\Quote;
use App\Invoice;
use App\Services\ServiceTradeService;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function export_csv(Request $request)
    {
    switch ($request->export_type) {
        case 'STQUOTES':
            $table = Quote::all();
            $filename = "STQUOTES_".time().".csv";
            $handle = fopen($filename, 'w+');
            fputcsv($handle, array('id', 'rec','price', 'status','updated'));
        
            foreach($table as $row) {
                fputcsv($handle, array($row['id'], $row['rec'], $row['price'], $row['status'],$row['updated']));
            }
            fclose($handle);
        
            $headers = array(
                'Content-Type' => 'text/csv',
            );
            break;
        case 'STINVOICES':
            # code...
            $table = Invoice::all();
            $filename = "STQINVOICES_".time().".csv";
            $handle = fopen($filename, 'w+');
            fputcsv($handle, array('id', 'price', 'status','updated','rec'));
        
            foreach($table as $row) {
                fputcsv($handle, array($row['id'], $row['price'], $row['status'], $row['updated'],$row['rec']));
            }
            fclose($handle);
        
            $headers = array(
                'Content-Type' => 'text/csv',
            );
            break;
            case 'UNSCHEDULED_APPOINTMENTS':
            $st = new ServiceTradeService;
            $app_payload['method'] = 'GET';
            $createdAfter = strtotime('1 January 2018');
            $app_payload['endpoint'] = 'appointment?status=unscheduled&createdAfter='.$createdAfter.'';
            $appointments = json_decode($st->send($app_payload)->getBody()->getContents());
            $quotes = array();
            $filename = date('Y',$createdAfter)."-".date('Y')."_unscheduled_quoted_jobs.csv";
            $handle = fopen($filename, 'w+');
            fputcsv($handle, array('quote.id','quote.status','quote.price','quote.owner','quote.assignedTo','appointment.id', 'appointment.status','appointment.created', 'address.street','address.city','job.id','job.number','job.name'));
            foreach ($appointments->data->appointments as $app_key => $app_value) {
                array_push($quotes,$app_value->job->id);
            }
            print_r(gettype($quotes));
            return;
            $chunkedArray = array_chunk($quotes,50);
            $quotes_list = array();
            foreach($chunkedArray as $key => $list){
                $appointment_list = trim(json_encode($list), '[]');
                $chunkList = $this->getQuotes($appointment_list);
                array_push($quotes_list,$chunkList);
            }
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
            break;
        default:
        $filename = "EMPTY_".time().".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, array());
        fclose($handle);
        $headers = array(
            'Content-Type' => 'text/csv',
        );
            break;
    }
    return Response::download($filename, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function getQuotes($appointments)
    {
        $st = new ServiceTradeService;
        $quote_payload['method'] = 'GET';
        $quote_payload['endpoint'] = 'quote?jobId='.$appointments;
        $quote = json_decode($st->send($quote_payload)->getBody()->getContents());
        // return $quote;
        $quotes_array = array();
        foreach ($quote->data->quotes as $key => $value) {
           $quotes_array[$value->jobs[0]->id] = array(
            "quote.id" => $value->id,
            "quote.status" => $value->status,
            "quote.price" => $value->totalPrice,
            "quote.owner" => $value->owner->email,
            "quote.assignedTo" => (isset($value->assignedTo->email))?$value->assignedTo->email:"empty"
           );
        }
        return $quotes_array;
    } 
}
