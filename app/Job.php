<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    //
    protected $fillable = [
        'id', 'number', 'status','description','quote_id','invoice_id',
    ];
}
