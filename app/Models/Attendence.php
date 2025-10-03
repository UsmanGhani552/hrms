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

    public static function updateAttendence($data): array
    {
        $results = [
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];

        foreach ($data['entries'] as $index => $entry) {
            try {
                if (isset($entry['id'])) {
                    // Update existing record
                    $attendance = self::find($entry['id']);
                    if ($attendance) {
                        $attendance->timestamp = $entry['timestamp'];
                        $attendance->type = $entry['type'];
                        $attendance->user_id = $entry['user_id'];
                        $attendance->save();
                        $results['updated']++;
                    } else {
                        $results['errors'][] = "Record not found for ID: {$entry['id']}";
                    }
                } else {
                    // Create new record
                    Attendence::create([
                        'user_id' => $entry['user_id'],
                        'timestamp' => $entry['timestamp'],
                        'type' => $entry['type'],
                    ]);
                    $results['created']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing entry {$index}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
