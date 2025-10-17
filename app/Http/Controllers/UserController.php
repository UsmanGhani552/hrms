<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Shift;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::with('shift')->get();
            $users->map(function($user) {
                $user->role = $user->getRoleNames()->first();
                unset($user->roles);
            });
            
            return ResponseTrait::success('Users fetched successfully', $users);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error fetching users', $th);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = User::createUser($request->validated());
            return ResponseTrait::success('User created successfully', $user);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error creating user', $th);
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $user->updateUser($request->validated());
            return ResponseTrait::success('User updated successfully', $user);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error updating user', $th->getMessage());
        }
    }

    public function delete(User $user) {
        try {
            $user->delete();
            return ResponseTrait::success('User deleted successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error deleting user', $th);
        }
    }

    public function shifts() {
        try {
            $shifts = Shift::all();
            return ResponseTrait::success('Shifts fetched successfully', $shifts);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error fetching shifts', $th);
        }
    }

    public function roles() {
        try {
            $roles = Role::all();
            return ResponseTrait::success('Roles fetched successfully', $roles);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error fetching roles', $th);
        }
    }
}
