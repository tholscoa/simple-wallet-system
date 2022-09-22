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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_user_id');
            $table->unsignedBigInteger('beneficiary_user_id');
            $table->string('reference_no');
            $table->string('transaction_id');
            $table->longText('narration');
            $table->longText('other')->nullable();
            $table->boolean('status');
            $table->timestamps();

            $table->foreign('source_user_id')->references('id')->on('users');
            $table->foreign('beneficiary_user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
