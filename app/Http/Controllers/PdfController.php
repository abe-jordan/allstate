<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Smalot\PdfParser;
class PdfController extends Controller
{
    //
    public function pdf()
    {
                // Parse pdf file and build necessary objects.
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile('http://127.0.0.1:8000/images/test.pdf');
        
        // Retrieve all details from the pdf file.
        $details  = $pdf->getDetails();
        
        // Loop over each property to extract values (string or array).
        foreach ($details as $property => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            echo $property . ' => ' . $value . "\n";
        }
    }
}
