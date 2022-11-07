<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\LoanMaster;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Initialize Response
        $response = [];
        //total loans list for admin
        $totalLoanApplied=LoanMaster::viewTotalLoans();
        foreach($totalLoanApplied as $value)
        {
            if($value->approved_status == "P"){
                $status = "Pending";
            }elseif($value->approved_status == "A"){
                $status = "Approved";
            }elseif($value->approved_status == "R"){
                $status = "Rejected";
            }else{
                $status="";
            }
            if($value->loan_status == "N"){
                $loanStatus = "Pending";
            }elseif($value->loan_status == "Y"){
                $loanStatus = "Fully Paid";
            }

            $response[] = [
            'customer' => $value->name,
            'loanNo' => $value->loan_id,
            'amount' => $value->amount,
            'loanApplicationdate'=>$value->apply_date,
            'status' => $status,
            'loan_states'=> $loanStatus
            ];            
        }
        return response()->json($response, 200);

    }
    public function approvedLoan(Request $request)
    {
        $response = [];
        $validator = Validator::make($request->all(), [
            'loan_no' => 'required|alpha_num',
            'loan_approve' => 'required|in:A',
        ],
        [
            'loan_no.required'      => 'Loan no is required.',
            'loan_no.alpha_num'     => 'The loan no must only contain letters and numbers.',
            'loan_approve.required' => 'Loan Approved flag is required.',
            'loan_approve.in'       => 'Please Enter only A for Approve.',
        ]);

        if ($validator->fails()) {
           throw new HttpResponseException(response()->json($validator->errors(), 422));
        }
        //validation for check loan number
        $validLoanNo=LoanMaster::where('loan_id', $request->loan_no)->count();
        if($validLoanNo < 1)
        {
            $response['message'] = 'Please Enter The Correct Loan Number!';
            return response()->json($response);
        }
        //validation for check is loan already settle
        $checkLoanSettlement=LoanMaster::where('loan_id', $request->loan_no)->first();
        if($checkLoanSettlement->loan_status == "Y")
        {
            $response['message'] = 'This Loan already settled.';
            return response()->json($response);
        }
        //validation for check is loan already approved
        $checkApproved=LoanMaster::where('loan_id', $request->loan_no)->first();
        if(trim($checkApproved->approved_status) == "A")
        {
            $response['message'] = 'Loan is already Approved.';
            return response()->json($response);
        }
        //loan approved process
        $updateLoanStatus=LoanMaster::loanApproved($request);
        if($updateLoanStatus){
            $response['message'] = 'Customer Loan Approved';
            return response()->json($response);
        }else{
            $response['message'] = 'Something Error Occur!';
            return response()->json($response);
        }
    }
}
