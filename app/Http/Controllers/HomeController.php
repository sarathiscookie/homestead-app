<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Http\Traits\CredentialsTrait;
use App\Models\Subscription;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use CredentialsTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $payments = Payment::get(['id', 'plan', 'amount']);

        $token = $this->gateway()->ClientToken()->generate();

        return view('home', ['payments' => $payments, 'token' => $token]);
    }

    /**
     * Select Subscription and store information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkout(Request $request)
    {
        $payment = Payment::where('id', $request->id)
            ->first();   

        if ($payment) {
            $gateway = $this->gateway();

            $amount = $payment->amount;
            $nonce = $request->payment_method_nonce;

            $result = $gateway->transaction()->sale([
                'amount' => $amount,
                'paymentMethodNonce' => $nonce,
                'customer' => [
                    'firstName' => auth()->user()->name,
                    'email' => auth()->user()->email
                ],
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);

            if ($result->success || !is_null($result->transaction)) {

                $transaction = $result->transaction;

                $this->store($payment->id);
    
                return back()->with('message', 'Transaction successful. The ID is:'. $transaction->id);
                
            } else {
                $errorString = "";
    
                foreach ($result->errors->deepAll() as $error) {
                    $errorString .= 'Error: ' . $error->code . ": " . $error->message . "\n";
                }
    
                return back()->withErrors('An error occurred with the message: '.$errorString);          
            }
        } else {
            abort(404);
        }
    }

    /**
     * Store subscription details and user information.
     *
     * * @param  int  $paymentId
     * @return \Illuminate\Http\Response
     */
    public function store($paymentId)
    {
        Subscription::create([
            'user_id' => auth()->user()->id,
            'payment_id' => $paymentId
        ]);
    }
}
