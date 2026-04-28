<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $primaryKey = 'meeting_id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'agenda',
        'meeting_date',
        'meeting_time',
        'location_or_link',
        'dyte_meeting_id',
        'created_by',
        'status',
        'created_at',
        'updated_at',
    ];
}
