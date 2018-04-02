<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefreshTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('refresh_tokens', function (Blueprint $table) {

            $table->bigInteger('character_id')->primary();

            $table->mediumText('refresh_token');
            $table->json('scopes');
            $table->dateTime('expires_on');
            $table->string('token');
            $table->integer('user_id')->default(0);
            $table->tinyInteger('expired')->default(0);

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

        Schema::dropIfExists('refresh_tokens');
    }
}
