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
        Schema::create('repaymentmasters', function (Blueprint $table) {
            $table->integer("customer_id");
            $table->string("txn_id",15);
            $table->integer("emi_count");
            $table->date("payable_date");
            $table->date("entry_date")->nullable();
            $table->double("principal_amount")->nullable();
            $table->double("interest_rate")->nullable();
            $table->double("total_payable")->nullable();
            $table->double("balance")->nullable();
            $table->char("repayment_status",1)->nullable();
            $table->string("loan_no",50)->nullable();
            $table->timestamp("last_modified")->nullable();
            

            $table->primary('txn_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repaymentmasters');
    }
};
