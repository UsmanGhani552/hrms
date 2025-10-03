<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() {
        try {
            $users = User::all();
            return ResponseTrait::success('Users fetched successfully', $users);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error fetching users', $th);
        }
    }
}
