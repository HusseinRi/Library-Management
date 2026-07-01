<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo',
        'role',
        'otp_code',
        'otp_expires_at',
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
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function myBooks()
    {
        return $this->hasMany(MyBook::class);
    }
    public function readingProgresses()
    {
        return $this->hasMany(ReadingProgress::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function getPictureUrlAttribute()
    {
        return $this->profile_photo
            ? asset(Storage::url($this->profile_photo))
            : null;
    }

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
}
