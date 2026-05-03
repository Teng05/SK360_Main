<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileApiToken extends Model
{
    protected $table = 'mobile_api_tokens';
    protected $primaryKey = 'mobile_api_token_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
