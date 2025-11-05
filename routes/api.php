<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\UserController;
use App\Models\Holiday;
use App\Services\ZKTecoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rats\Zkteco\Lib\ZKTeco;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard/user-stats', [DashboardController::class, 'userStats']);
    //atendence routes
    Route::get('/fetch-attendence', [AttendanceController::class, 'fetchAttendance']);
    Route::get('/fetch-attendence-by-user-id', [AttendanceController::class, 'fetchAttendenceByUserId']);
    Route::get('/fetch-current-attendence', [AttendanceController::class, 'fetchCurrentAttendence']);
    Route::post('/attendence/update', [AttendanceController::class, 'update']);

    Route::get('/payrolls/', [PayrollController::class, 'index']);
    Route::post('/payroll/store', [PayrollController::class, 'store']);
    Route::post('/payroll/update/{payroll}', [PayrollController::class, 'update']);
    Route::get('/payroll/delete/{payroll}', [PayrollController::class, 'delete']);
    
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function() {
        Route::get('/','index')->name('index');
        Route::post('/store', 'store')->name('store');
        Route::post('/update/{user}', 'update')->name('update');
        Route::delete('/delete/{user}', 'delete')->name('delete');
    });
    Route::controller(HolidayController::class)->prefix('holidays')->name('holidays.')->group(function() {
        Route::get('/','index')->name('index');
        Route::post('/store', 'store')->name('store');
        Route::post('/update/{holiday}', 'update')->name('update');
        Route::delete('/delete/{holiday}', 'delete')->name('delete');
    });
    Route::controller(LeaveController::class)->prefix('leaves')->name('leaves.')->group(function() {
        Route::get('/','index')->name('index');
        Route::post('/store', 'store')->name('store');
        Route::post('/update/{leave}', 'update')->name('update');
        Route::delete('/delete/{leave}', 'delete')->name('delete');
        Route::post('/approve/{leave}', 'approve')->name('approve');
    });
    Route::get('/shifts', [UserController::class, 'shifts']);
    Route::get('/roles', [UserController::class, 'roles']);
});

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

Route::get('/socket-test-detail', function () {
    $results = [];
    $ip = '154.57.213.84';
    $port = 2370;

    // Test 1: TCP Socket
    $tcpSocket = @fsockopen($ip, $port, $errno, $errstr, 5);
    $results['TCP Socket'] = $tcpSocket ? '✅ SUCCESS' : "❌ FAILED: $errstr";
    if ($tcpSocket) fclose($tcpSocket);

    // Test 2: UDP Socket (different approach)
    $udpSocket = @fsockopen("udp://$ip", $port, $errno, $errstr, 5);
    $results['UDP Socket Create'] = $udpSocket ? '✅ SUCCESS' : "❌ FAILED: $errstr";

    // Test 3: UDP Write (if socket created)
    if ($udpSocket) {
        $testData = "TEST";
        $writeResult = fwrite($udpSocket, $testData);
        $results['UDP Socket Write'] = $writeResult ? "✅ SUCCESS: wrote $writeResult bytes" : "❌ FAILED: Cannot write";
        fclose($udpSocket);
    }

    // Test 4: Low-level socket functions
    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    $results['socket_create()'] = $sock ? '✅ SUCCESS' : '❌ FAILED: ' . socket_strerror(socket_last_error());

    if ($sock) {
        $bind = @socket_bind($sock, '0.0.0.0', 0);
        $results['socket_bind()'] = $bind ? '✅ SUCCESS' : '❌ FAILED: ' . socket_strerror(socket_last_error());

        if ($bind) {
            $send = @socket_sendto($sock, "TEST", 4, 0, $ip, $port);
            $results['socket_sendto()'] = $send ? "✅ SUCCESS: sent $send bytes" : '❌ FAILED: ' . socket_strerror(socket_last_error());
        }
        socket_close($sock);
    }

    return $results;
});
