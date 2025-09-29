<?php 

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Morning Shift',
                'check_in_from' => '08:00:00',
                'check_in_to' => '17:00:00',
            ],
            [
                'name' => 'Night Shift',
                'check_in_from' => '17:00:00',
                'check_in_to' => '08:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}