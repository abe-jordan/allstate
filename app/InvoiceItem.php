<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    //
    protected $fillable = [
        'id', 'invoice_id', 'price','quantity','description',
    ];
}
