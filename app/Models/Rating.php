<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'rater_id',
        'ratee_id',
        'score',
        'comments',
    ];

    public static function giveRating(array $data): Rating
    {
        return self::updateOrCreate(
            [
                'rater_id' => auth()->id(),
                'ratee_id' => $data['ratee_id'],
            ],
            [
                'score' => $data['score'],
                'comments' => $data['comments'] ?? null,
            ]
        );
    }
    public function rater() {
        return $this->belongsTo(User::class, 'rater_id');
    }
    public function ratee() {
        return $this->belongsTo(User::class, 'ratee_id');
    }
}
