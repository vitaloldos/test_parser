<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    /* Add Soft Deleting trate*/
    use SoftDeletes;

    public $table = "test";

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'card_number', 'user_email'
    ];

}
