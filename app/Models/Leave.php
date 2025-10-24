<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'applied_at',
    ];

    public static function createLeave(array $data): Leave {
        $data['user_id'] = auth()->id();
        return self::create($data);
    }

    public function updateLeave(array $data): bool {
        return $this->update($data);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
