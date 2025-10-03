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

    public static function updateAttendence($data): void {
        foreach ($data['entries'] as $entry) {
            $attendence = self::find($entry['id']);
            if ($attendence) {
                $attendence->timestamp = $entry['timestamp']; 
                $attendence->save();
            }else {
                Attendence::create([
                    'user_id' => $data['user_id'],
                    'timestamp' => $data['timestamp'],
                    'type' => $data['type'],
                ]);
            }
        }
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
