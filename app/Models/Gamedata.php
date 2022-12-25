<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gamedata extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'Reffer',
        'PlayerAvatarUrl',
        'FortuneWheelLastFreeTime',
        'Chats',
        'Emoji',
        'Coins',
        'GamesPlayed',
        'AvatarIndex',
        'FourPlayerWins',
        'LoggedType',
        'PrivateTableWins',
        'TitleFirstLogin',
        'TotalEarnings',
        'TwoPlayerWins',
    ];
}
