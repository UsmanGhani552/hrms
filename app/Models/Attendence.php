<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendence extends Model
{
    protected $fillable = [
        'user_id',
        'timestamp',
        'type',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
