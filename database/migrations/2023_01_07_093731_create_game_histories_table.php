<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_histories', function (Blueprint $table) {
            $table->id();
            $table->string('room_name');
            $table->string('game_mode');
            $table->string('game_type');
            $table->string('game_money');
            $table->string('user_count');
            $table->string('required_player');
            $table->string('user_id');
            $table->string('player_name');
            $table->string('coins');
            $table->string('winner_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_histories');
    }
};
