<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LoanMaster;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RepaymentMaster extends Model
{
    use HasFactory;
    protected $table = 'repaymentmasters';
    public function __construct()
    {
        return $this->belongsTo(LoanMaster::class);
    }

    public static function saveInfo($request)
    {
        $amount=$request->amount;
        $term=$request->term;
        $emi=number_format((float)$amount/$term, 2, '.', ''); //two decimal place
        $balance=number_format((float)($amount-$emi*$term), 2, '.', '');
        $InstalmentAmt=$emi+$balance;        
        $current_date= Carbon::now();
        for($i=1;$i<=$term;$i++)
        {
            $modifiedMutable = Carbon::parse($current_date->add(7, 'day'))->toDateString();
            $success=1;
            $seqNo   =DB::select("select nextval('txn_seq')");
            $txnSeqNo = "TXN".$seqNo[0]->nextval.date("dmY");
            DB::beginTransaction();
            try {            
                DB::table('repaymentmasters')
                    ->insert([
                        'customer_id'       => auth()->user()->id,
                        'txn_id'            => $txnSeqNo,
                        'emi_count'         => $i,
                        'principal_amount'  => $InstalmentAmt,
                        // 'balance'           => $amount,
                        'repayment_status'  => "N" //paid=Y,unknown=N
                    ]);
               
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $success = 0;
            }
            $InstalmentAmt=$emi;
        }
        return $success;
    }
    public static function PayEMI($request,$Id)
    {
       $checkValidation=DB::select("Select lm.balance,lm.amount,lm.term,rm.emi_count,lm.customer_id,lm.loan_status,rm.txn_id, rm.principal_amount, rm.repayment_status from repaymentmasters rm inner join loanmasters lm on rm.customer_id=lm.customer_id where rm.customer_id=".$Id." and rm.repayment_status='N' and lm.loan_status='N' and lm.loan_id='".$request->loan_no."' order by rm.emi_count LIMIT 1");
        foreach ($checkValidation as $value) {            
            $balance=($value->balance - $request->due_amount);
            if($balance==0){
                $loanStatus="Y";
            }else{
                $loanStatus="N";
            }
            $success=1;
            DB::beginTransaction();
            try {
                DB::table('repaymentmasters')
                    ->where('emi_count', $value->emi_count)
                    ->where('customer_id', $value->customer_id)
                    ->where('repayment_status',"N")
                    ->update(
                        [
                            'total_payable'           => $request->due_amount,
                            'entry_date'              => date('Y-m-d'),
                            'repayment_status'        => "Y", //paid=Y,unknown=N
                            'last_modified'           =>date("Y-m-d H:i:s")
                        ]);

                DB::table('loanmasters')
                    ->where('customer_id', $value->customer_id)
                    ->where('loan_status', "N")
                    ->update(
                        [
                            'loan_status' =>$loanStatus,
                            'balance' => $balance
                        ]);
                
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $success = 0;
            }
            return ['success' => $success, 'transaction_id' => $value->txn_id ];
        }
    }
    public static function getData($id)
    {
        return DB::select("Select lm.*,rm.* from repaymentmasters rm inner join loanmasters lm on rm.customer_id=lm.customer_id where rm.customer_id=".$id." and lm.loan_status='N' and rm.repayment_status='N' order by rm.emi_count asc");
    }
    public static function getTransactions($id,$loanId)
    {
        return DB::table('repaymentmasters')
                ->where('customer_id', $id)
                ->where('loan_no',$loanId)
                ->get();
    }
}
