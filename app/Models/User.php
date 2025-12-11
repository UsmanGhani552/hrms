<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'shift_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function createUser(array $data): User {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->assignRole($data['role']);
        return $user;
    }
    public function updateUser(array $data): User {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $this->update($data);
        $this->syncRoles($data['role']);
        return $this;
    }

    public function updateProfile(array $data): User {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $this->update($data);
        return $this;
    }

    public function shift() {
        return $this->belongsTo(Shift::class);
    }
    public function leaves() {
        return $this->hasMany(Leave::class,'user_id');
    }

    public function attendences() {
        return $this->hasMany(Attendence::class);
    }
}
