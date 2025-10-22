<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'description'];

    public static function createHoliday(array $data): void
    {
        $holiday = self::create($data);
        $holiday->shiftHolidays()->attach($data['shift_ids']);
    }

    public function updateHoliday(array $data): void
    {
        $this->update($data);
        $this->shiftHolidays()->sync($data['shift_ids']);
    }

    public function shiftHolidays() {
        return $this->belongsToMany(Shift::class, 'shift_holidays');
    }
}
