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
        Schema::create('loanmasters', function (Blueprint $table) {
            $table->id();
            $table->integer("customer_id");
            $table->string("loan_id",15)->unique();
            $table->double("amount",12,2);
            $table->integer("term");
            $table->date("apply_date")->nullable();
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->integer("emi_period")->nullable();
            $table->char("approved_status",1)->nullable();
            $table->char("loan_status",1)->nullable();
            $table->double("balance")->nullable();
            $table->timestamp("last_modified")->nullable();

            // $table->primary(['id', 'loan_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loanmasters');
    }
};
