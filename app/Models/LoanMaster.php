<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RepaymentMaster;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanMaster extends Model
{
    use HasFactory;
    protected $table = 'loanmasters';
    public static function viewCusLoan($id)
    {
        return DB::table('loanmasters')
                ->where('customer_id', $id)
                ->get();
    }
    public static function store($request)
    {
        $success = 1;
        DB::beginTransaction();
        try {            
            DB::table('loanmasters')
                ->insert([
                    'customer_id'           => $request['customer_id'],
                    'loan_id'               => $request['loan_id'],
                    'amount'                => $request['amount'],
                    'term'                  => $request['term'],
                    'apply_date'            => $request['apply_date'],
                    'start_date'            => $request['start_date'],
                    'end_date'              => $request['end_date'],
                    'emi_period'            => $request['emi_period'],
                    'approved_status'       => $request['approved_status'],
                    'loan_status'           => $request['loan_status'],
                    'balance'               => $request['balance'],
                    'last_modified'         => $request['last_modified']
                ]);
           
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $success = 0;
        }
        return $success;
    }
    public static function viewTotalLoans()
    {
        return DB::table('loanmasters')
                ->select('loanmasters.*', 'users.name')
                ->join('users', 'users.id', '=', 'loanmasters.customer_id')
                ->orderBy('id', 'DESC')
                ->get();
    }
    public static function loanApproved($request)
    {
        $LoanMaster=LoanMaster::where(DB::raw(trim('loan_id')), $request->loan_no)->first();
        $repayFreq=$LoanMaster->emi_period; //as per mentioned
        $term=$LoanMaster->term;
        $totaldays=$repayFreq*$term;
        $current_date= Carbon::now();
        $sattlementDate = date('Y-m-d', strtotime('+'.$totaldays.' day', time()));
        $success=1;
        DB::beginTransaction();
        try {
            DB::table('loanmasters')
                ->where(DB::raw(trim('loan_id')), $request->loan_no)
                ->update(
                    [
                        'start_date'        =>date("Y-m-d"),
                        'end_date'          =>$sattlementDate,
                        'approved_status'   =>trim($request->loan_approve),
                        'last_modified'     =>date("Y-m-d H:i:s")
                    ]);
            $updateDueDate=RepaymentMaster::getData($LoanMaster->customer_id);
            foreach ($updateDueDate as $value) {
                $nxtDate=Carbon::parse($current_date->add($repayFreq, 'day'))->toDateString();
                DB::table('repaymentmasters')
                    ->where('customer_id', $value->customer_id)
                    ->where('emi_count', $value->emi_count)
                    ->update(
                        [
                            'payable_date'       => $nxtDate,
                            'loan_no'            => trim($request->loan_no),
                            'last_modified'      => date("Y-m-d H:i:s")
                        ]);
            }       
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $success = 0;
        }
        return $success;
    }
    public static function CustomerLoan($id)
    {
        return DB::table('loanmasters')
                ->select('loanmasters.*', 'users.name')
                ->join('users', 'users.id', '=', 'loanmasters.customer_id')
                ->where(DB::raw(trim('loanmasters.customer_id')), $id)
                ->get();
    }
}
