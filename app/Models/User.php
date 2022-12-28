<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Multicaret\Acquaintances\Traits\Friendable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Friendable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'facebook_id',
        'custom_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function gamedata() {
        return $this->hasOne(Gamedata::class);
    }

    public static function createUser(string $name, $email, $phone, $password, $facebook_id, $custom_id) {
        $user = new User();
        $user->name = $name;
        if(!is_null($email)) $user->email = $email;
        if(!is_null($phone)) $user->phone = $phone;
        if(!is_null($password)) $user->password = bcrypt($password);
        if(!is_null($facebook_id)) $user->facebook_id = $facebook_id;
        if(!is_null($custom_id)) $user->custom_id = $custom_id;
        $user->save();

        Gamedata::create([
            'user_id' => $user['id'],
            'Coins' => env('JOINING_BONUS'),
        ]);
        return $user;
    }

    public static function deleteUser(User $user) {
        $user->gamedata->delete();
        $user->delete();
    }
}
