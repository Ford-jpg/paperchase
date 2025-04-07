<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasUlids;

    protected $fillable = [
        'acronym',
        'name',
        'type',
        'head_name',
        'designation'
    ];
}
