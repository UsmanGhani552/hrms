<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Services\ZKTecoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rats\Zkteco\Lib\ZKTeco;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login',[AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/fetch-attendence', [AttendanceController::class, 'fetchAttendance']);
Route::get('/fetch-users', [AttendanceController::class, 'fetchUsers']);

Route::get('/test-zkteco', function () {
    $ip = '154.57.213.84';
    $port = 2370;
    $timeout = 3;

    try {
        $zk = new ZKTeco($ip, $port);
        
        echo "Connecting...\n";
        $connected = $zk->connect();
        echo "Connected: " . ($connected ? 'YES' : 'NO') . "\n";
        
        if ($connected) {
            echo "Device Version: " . $zk->version() . "\n";
            echo "Device Time: " . $zk->getTime() . "\n";
            
            $users = $zk->getUser();
            echo "Users Found: " . count($users) . "\n";
            
            $zk->disconnect();
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
});
