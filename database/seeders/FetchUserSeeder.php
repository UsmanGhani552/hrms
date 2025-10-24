<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ZKTecoService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FetchUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected $zkService;

    public function __construct(ZKTecoService $zkService)
    {
        $this->zkService = $zkService;
    }
    public function run(): void
    {
        $users  = $this->zkService->getUsers();
        $users = array_filter($users, function($user) {
            $hrIds = [3000];
            return $user['role'] == 4 || in_array($user['userid'], $hrIds);
        });
        foreach ($users as $user) {
            $dbUser = User::where('id', $user['userid'])->first();
            if ($dbUser) continue;
            $formattedName = str_replace(' ', '_', $user['name']);
            $employee = User::updateOrCreate(
                ['id' => $user['userid']],
                [
                    'name' => $user['name'],
                    'email' => $formattedName .'@opusgeeks.com',
                    'password' => Hash::make('employee123'),
                    'shift_id' => 1
                ]
            );
            $employee->assignRole('employee');
            Log::info('User fetched/created: ' . $employee);
        }
    }
}
