<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSubmission extends Model
{
    protected $table = 'report_submissions';
    protected $primaryKey = 'report_submission_id';

    protected $fillable = [
        'user_id',
        'report_title',
        'submission_method',
        'report_file_path',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
