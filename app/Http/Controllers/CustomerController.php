<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\LoanMaster;
use App\Models\RepaymentMaster;
use Carbon\Carbon;

class CustomerController extends Controller
{
    //customer profile
    public function index(Request $request)
    {
        header('content-type: application/json');
        echo json_encode(["Welcome ".auth()->user()->name.""])."\n";
        // Initialize Response
        $response = [];
        $results=[];
        $chkCustomerLoan=LoanMaster::CustomerLoan(auth()->user()->id);
        if(count($chkCustomerLoan) > 0)
        {
            foreach($chkCustomerLoan as $data)
            {
                if($data->approved_status == "P"){
                    $status = "Pending";
                }elseif($data->approved_status == "A"){
                    $status = "Approved";
                }elseif($data->approved_status == "R"){
                    $status = "Rejected";
                }else{
                    $status="";
                }
                if($data->loan_status == "N"){
                    $loanStatus = "Pending";
                }elseif($data->loan_status == "Y"){
                    $loanStatus = "Paid";
                }
                $responseCus = [
                    'customer' => $data->name,
                    'amount'    =>$data->amount,
                    'loanNo' => $data->loan_id,
                    'loanApplicationdate'=>$data->apply_date,
                    'due_balance' =>$data->balance,
                    'status' => $status,
                    'loan_state' => $loanStatus
                ];
                header('content-type: application/json');
                echo json_encode($responseCus);
                echo "\n";
                if($status == "Approved" && $data->loan_status=="N")
                {
                    $repayData=RepaymentMaster::getTransactions($data->customer_id,$data->loan_id);
                    foreach($repayData as $value)
                    {
                        if(trim($value->repayment_status) == "Y")
                        {
                            $loanStates="Paid";
                        }else{
                            $loanStates="Pending";
                        }                  

                        $response = [
                            'loan_no'   => $value->loan_no,
                            'installment' => $value->emi_count,
                            'amount'    =>$value->principal_amount,
                            'due_date' => $value->payable_date,
                            'status' => $loanStates
                        ];
                        array_push($results, $response);
                    }
                    return response()->json($results, 200);
                }
            }
        }else{
            return response()->json(['message' => 'Apply for a loan.']);
        }
    }
    public function requestloan(Request $request)
    {
        // Initialize Response
        $response = [];
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'term' => 'required|numeric',
        ]);
        if ($validator->fails()) {
           throw new HttpResponseException(response()->json($validator->errors(), 422));
        }
        $checkLoanVal=LoanMaster::viewCusLoan(auth()->user()->id);
        if(count($checkLoanVal) > 0)
        {
            foreach($checkLoanVal as $val)
            {
                if($val->loan_status == "N" && $val->approved_status == "P"){
                    $response = [
                        'message' => 'You have already Applied for a loan.',
                    ];
                    return response()->json($response); 
                                      
                }elseif($val->loan_status == "N" && $val->approved_status == "A"){
                    $response = [
                        'message' => 'Please Clear the Previous Loan First.',
                    ];
                    return response()->json($response);
                }
                                
            }            
        }
        $freq=7; //weekly frequency
        $params=array(
            "customer_id" => auth()->user()->id,
            "loan_id"   => substr(strtoupper(auth()->user()->name),0,3).rand(10000,99999),            
            "amount" => $request->amount,
            "term" =>   $request->term,
            "apply_date" => Carbon::now()->toDateString(),
            "start_date" => null,
            "end_date" =>   null,
            "emi_period" => $freq,
            "approved_status" =>  "P", //A=approved,P=Pending 
            "loan_status" =>"N", //Y=Paid,N=Pending
            "balance" => $request->amount,
            "last_modified" => null
        );
        $saveLoanInfo=LoanMaster::store($params);
        if($saveLoanInfo)
        {
           $saveEMIInfo=RepaymentMaster::saveInfo($request);
           if($saveEMIInfo){
                $data=RepaymentMaster::getData(auth()->user()->id,);
                $totalDays=$request->term*$freq;
                $currentDate= Carbon::now();
                $emi=$request->amount/$request->term;
                $emiamout=number_format((float)$emi, 2, '.', '');
                header('content-type: application/json');
                echo json_encode(['message' => "Your request for loan application is accepted. Admin will verify and approve it."])."\n";
                echo json_encode(['installments' => "Your selected scheduled for repayment:"])."\n";
                foreach($data as $val)
                {   
                    $modifiedMutable = Carbon::parse($currentDate->add($freq, 'day'))->toDateString();
                    $response = [
                        'loan_no'       => $val->loan_id,
                        'installment'   => $val->emi_count,
                        'amount'        => $val->principal_amount,
                        'due_date'      => $val->payable_date ?? "Not Approved Yet",
                    ];
                    header('content-type: application/json');
                    echo json_encode($response)."\n";
                }
           }else{ 
                return response()->json(['error' => 'Something Went Wrong !']);
           }
           
        }else{
            return response()->json(['error' => 'Something Went Wrong !']);
        }
    }
    public function RepaymentAmount(Request $request)
    {
        // Initialize Response
        $response = [];
        $validator = Validator::make($request->all(), [
            'due_amount'    => 'required|numeric',
            'loan_no'       => 'required|alpha_num',
        ]);
        if ($validator->fails()) {
           throw new HttpResponseException(response()->json($validator->errors(), 422));
        }
        $validLoanNo=LoanMaster::where('loan_id', $request->loan_no)->count();
        if($validLoanNo < 1)
        {
            $response['message'] = 'Loan Number is not correct!';
            return response()->json($response);
        }
        $validatePayment=LoanMaster::where('customer_id', auth()->user()->id)->where('loan_id',$request->loan_no)->first();
        if($validatePayment->loan_status == "Y" && $validatePayment->balance <= 0){
            return response()->json(['message' => 'Your already paid your all dues. Loan is settled.']);
        }elseif($validatePayment->approved_status == "P"){
            return response()->json(['message' => 'Your can not pay your installments as your loan amount is not approved yet.']);
        }
        $checkInstalmentPrice=RepaymentMaster::where('customer_id', auth()->user()->id)->where('repayment_status',"N")->first();
        if($request->due_amount != $checkInstalmentPrice->principal_amount)
        {
            return response()->json(['message' => 'Please pay your repayment with amount equal to the scheduled repayment']);
        }
        $saveEMIInfo=RepaymentMaster::PayEMI($request,auth()->user()->id);
        if($saveEMIInfo['success'] >0){
            $response=[
                'message' => 'Repayment completed.',
                'transaction_id' => $saveEMIInfo['transaction_id']
            ];
            return response()->json($response);
        }else{
            return response()->json(['message' => 'Something Error Occur!']);
        }
    }
}
