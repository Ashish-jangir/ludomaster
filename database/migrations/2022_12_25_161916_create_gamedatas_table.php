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
        Schema::create('gamedatas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('Reffer')->nullable();
            $table->string('PlayerAvatarUrl')->nullable();
            $table->string('FortuneWheelLastFreeTime')->nullable();
            $table->text('Chats')->nullable();
            $table->text('Emoji')->nullable();
            $table->string('Coins')->nullable();
            $table->string('GamesPlayed')->nullable();
            $table->string('AvatarIndex')->nullable();
            $table->string('FourPlayerWins')->nullable();
            $table->string('LoggedType')->nullable();
            $table->string('PrivateTableWins')->nullable();
            $table->string('TitleFirstLogin')->nullable();
            $table->string('TotalEarnings')->nullable();
            $table->string('TwoPlayerWins')->nullable();
            $table->string('PhotonToken')->nullable();
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
        Schema::dropIfExists('gamedatas');
    }
};
