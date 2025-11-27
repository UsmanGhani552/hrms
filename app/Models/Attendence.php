<?php

namespace App\Models;

use Exception;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;

class Attendence extends Model
{
    protected $fillable = [
        'user_id',
        'timestamp',
        'date',
        'type',
    ];

    // public function delete()
    // {
    //     // Your custom logic
    //     throw new Exception("Attendance deleted for user: {$this->user_id}");

    //     // Call parent delete (VERY IMPORTANT)
    //     return parent::delete();
    // }

    public static function updateAttendence($data): array
    {
        $results = [
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];

        foreach ($data['entries'] as $index => $entry) {
            try {
                FacadesLog::info('Processing entry: ', $entry);
                if (isset($entry['id'])) {
                    // Update existing record
                    $attendance = self::find($entry['id']);
                    if ($attendance) {
                        $attendance->timestamp = $entry['timestamp'];
                        $attendance->date = date('Y-m-d', strtotime($entry['timestamp']));
                        $attendance->type = $entry['type'];
                        $attendance->user_id = $entry['user_id'];
                        $attendance->save();
                        $results['updated']++;
                    } else {
                        $results['errors'][] = "Record not found for ID: {$entry['id']}";
                    }
                } else {
                    // Create new record
                    $attendance = Attendence::create([
                        'user_id' => $entry['user_id'],
                        'timestamp' => $entry['timestamp'],
                        'type' => $entry['type'],
                        'date' => date('Y-m-d', strtotime($entry['timestamp'])),
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
