<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Http\Traits\CredentialsTrait;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
        $payments = Payment::where('status', 'enabled')
            ->get(['id', 'plan', 'amount']);

        $subscription = auth()->user()->subscription;

        $token = $this->gateway()->ClientToken()->generate();

        return view('home', ['payments' => $payments, 'token' => $token, 'subscription' => $subscription]);
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

            $result = $gateway->transaction()->sale([
                'amount' => $payment->amount,
                'paymentMethodNonce' => $request->payment_method_nonce,
                'customer' => [
                    'firstName' => auth()->user()->name,
                    'email' => auth()->user()->email
                ],
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);

            if ($result->success) {

                $transaction = $result->transaction;

                $this->store($payment, $transaction);
    
                return redirect('/home')->with('message', 'Transaction successful. The ID is:'. $transaction->id);
                
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
     * @param  object  $payment
     * @param  object  $transaction
     * @return \Illuminate\Http\Response
     */
    public function store($payment, $transaction)
    {
        Subscription::create([
            'user_id' => auth()->user()->id,
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'ends_at' => $this->calculateDate($payment->plan)
        ]);
    }

    /**
     * Generate date and time based on plan.
     *
     * @param  string  $plan
     * @return \Illuminate\Http\Response
     */
    public function calculateDate($plan)
    {
        if ($plan === 'Yearly') {
            $newDateTime = Carbon::now()->addYear();
        } else {
            $newDateTime = Carbon::now()->addMonth();
        }

        return $newDateTime;
    }
}
