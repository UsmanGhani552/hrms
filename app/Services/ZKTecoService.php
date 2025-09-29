<?php

namespace App\Services;

use Rats\Zkteco\Lib\ZKTeco;
use Exception;
use Illuminate\Support\Facades\Log;

class ZKTecoService
{
    private $zk;
    private $ip;
    private $port;
    private $timeout;

    public function __construct($ip = null, $port = 2370, $timeout = 5)
    {
        $this->ip = $ip ?? config('zkteco.ip');
        $this->port = $port ?? config('zkteco.port');
        $this->timeout = $timeout;
        $this->zk = new ZKTeco($this->ip, $this->port);
    }

    public function connect()
    {
        try {
            $connected = $this->zk->connect();
            if (!$connected) {
                throw new Exception('Failed to connect to ZKTeco device');
            }
            return true;
        } catch (Exception $e) {
            Log::error('ZKTeco Connection Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect()
    {
        try {
            $this->zk->disconnect();
            return true;
        } catch (Exception $e) {
            Log::error('ZKTeco Disconnection Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAttendance()
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $attendance = $this->zk->getAttendance();
            $this->disconnect();
            
            return $this->formatAttendance($attendance);
        } catch (Exception $e) {
            Log::error('ZKTeco Attendance Fetch Failed: ' . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    public function getUsers()
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $users = $this->zk->getUser();
            $this->disconnect();
            
            return $users ?: [];
        } catch (Exception $e) {
            Log::error('ZKTeco Users Fetch Failed: ' . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    public function clearAttendance()
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $result = $this->zk->clearAttendance();
            $this->disconnect();
            return $result;
        } catch (Exception $e) {
            Log::error('ZKTeco Clear Attendance Failed: ' . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    public function testConnection()
    {
        if ($this->connect()) {
            $this->disconnect();
            return true;
        }
        return false;
    }

    public function getDeviceInfo()
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $info = [
                'device_name' => $this->zk->deviceName(),
                'platform' => $this->zk->platform(),
                'serial_number' => $this->zk->serialNumber(),
            ];
            $this->disconnect();
            
            return $info;
        } catch (Exception $e) {
            Log::error('ZKTeco Device Info Failed: ' . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    private function formatAttendance($attendanceData)
    {
        $formatted = [];
        
        if (!is_array($attendanceData)) {
            return $formatted;
        }
        
        foreach ($attendanceData as $record) {
            if (is_array($record) || is_object($record)) {
                $formatted[] = [
                    'uid' => $record['uid'] ?? $record->uid ?? null,
                    'user_id' => $record['id'] ?? $record->id ?? $record['user_id'] ?? $record->user_id ?? null,
                    'timestamp' => $this->parseTimestamp($record['timestamp'] ?? $record->timestamp ?? $record['date'] ?? $record->date ?? null),
                    'status' => $record['state'] ?? $record->state ?? $record['status'] ?? $record->status ?? 'unknown',
                    'verify_type' => $record['verified'] ?? $record->verified ?? $record['verify_type'] ?? $record->verify_type ?? 0,
                    'work_code' => $record['workcode'] ?? $record->workcode ?? null,
                ];
            }
        }
        
        return $formatted;
    }

    private function parseTimestamp($timestamp)
    {
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        if (is_string($timestamp)) {
            // Try to parse various date formats
            try {
                return date('Y-m-d H:i:s', strtotime($timestamp));
            } catch (Exception $e) {
                return now()->toDateTimeString();
            }
        }
        
        return now()->toDateTimeString();
    }
}