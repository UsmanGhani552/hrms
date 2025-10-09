<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\AttendenceSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchAttendence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-attendence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(AttendenceSeeder $attendenceSeeder)
    {
        try {
            DB::table('attendences')->truncate(); // Optional: Clear existing records
            $attendenceSeeder->run();
            $this->info('✅ Attendance data fetched and processed successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Error fetching attendance: ' . $e->getMessage());
            Log::error('Attendance fetch failed: ' . $e->getMessage());
        }
    }
}
