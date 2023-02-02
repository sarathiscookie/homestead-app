<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Http\Traits\CredentialsTrait;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    //TODO: Error reports store in to log file.
    //TODO: Create a webhook to update the subscriptions->next_billing_at.
    //TODO: Currently just disabled radio buttons in frontend. Backend validation not added.
    use CredentialsTrait;

    const PAYMENT_YEARLY_TYPE = 'Yearly';
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
     * Create braintree customer functionality.
     *
     * @return \Illuminate\Http\Response
     */
    public function createCustomer()
    {
        return $this->gateway()->customer()->create([
            'firstName' => auth()->user()->name,
            'email' => auth()->user()->email
        ]);
    }

    /**
     * Create braintree payment method.
     *
     * @return \Illuminate\Http\Response
     */
    public function createPaymentMethod($customerId, $paymentMethodNonce)
    {
        try {
            return $this->gateway()->paymentMethod()->create([
                'customerId' => $customerId,
                'paymentMethodNonce' => $paymentMethodNonce
            ]);
        } catch (\Exception $e) {
            return back()->withErrors('Something went wrong!');
        }
    }

    /**
     * Generate date and time based on plan.
     *
     * @param  string  $plan
     * @return \Illuminate\Http\Response
     */
    public function calculateDate($payment, $transaction)
    {
        if ($payment->plan !== self::PAYMENT_YEARLY_TYPE) {
            return Carbon::parse($transaction->nextBillingDate)->format('Y-m-d');
        }
    }

    /**
     * Transaction functionality for yearly plan.
     *
     * @param  object  $payment
     * @param  string  $paymentMethodNonce
     * @param  string  $customerId
     * @return \Illuminate\Http\Response
     */
    public function transactionSale($payment, $paymentMethodNonce, $customerId)
    {
        $result = $this->gateway()->transaction()->sale([
            'amount' => $payment->amount,
            'paymentMethodNonce' => $paymentMethodNonce,
            'customer' => [
                'firstName' => auth()->user()->name,
                'email' => auth()->user()->email
            ],
            'options' => [
                'submitForSettlement' => true
            ]
        ]);

        if ($result->success) {
            $this->store($payment, $result->transaction, $customerId);

            return redirect('/home')->with('message', 'Transaction successful. The ID is
            :'. $result->transaction->id);
            
        } else {
            $errorString = "";

            foreach ($result->errors->deepAll() as $error) {
                $errorString .= 'Error: ' . $error->code . ": " . $error->message . "\n";
            }

            return back()->withErrors('An error occurred with the message: '.$errorString);
        }
    }

    /**
     * Subscription functionality for monthly plan.
     *
     * @param  object  $payment
     * @param  string  $paymentMethodNonce
     * @param  string  $customerId
     * @return \Illuminate\Http\Response
     */
    public function subscriptionCreate($payment, $paymentMethodNonce, $customerId)
    {
        $paymentMethod = $this->createPaymentMethod($customerId, $paymentMethodNonce);

        if ($paymentMethod->success) {
            $result = $this->gateway()->subscription()->create([
                'paymentMethodToken' => $paymentMethod->paymentMethod->token,
                'planId' => 'monthly_subscriber',
            ]);

            if ($result->success) {
                $this->store($payment, $result->subscription, $customerId);
    
                return redirect('/home')->with('message', 'Subscription successful. The ID is
                :'. $result->subscription->id .'. Next billing date is: '
                . Carbon::parse( $result->subscription->nextBillingDate)->format('Y-m-d'));
                
            } else {
                $errorString = "";
    
                foreach ($result->errors->deepAll() as $error) {
                    $errorString .= 'Error: ' . $error->code . ": " . $error->message . "\n";
                }
    
                return back()->withErrors('An error occurred with the message: '.$errorString);
            }
        } else {
            return back()->withErrors('An error occurred with the message: Payment method token generation
             failed. Customer ID: '.$customerId);
        }
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

        return view('home', ['payments' => $payments, 'token' => $token,
         'subscription' => $subscription, 'paymentYearlyType' => self::PAYMENT_YEARLY_TYPE]);
    }

    /**
     * Select Subscription and store information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkout(Request $request)
    {
        $customerId = '';

        $payment = Payment::where('id', $request->id)
            ->first();

        if ($payment) {
            $customer = $this->createCustomer();

            if ($customer->success) {
                $customerId = $customer->customer->id;
            }

            if ($payment->plan === self::PAYMENT_YEARLY_TYPE) {
                // Yearly checkout.
                return $this->transactionSale($payment, $request->payment_method_nonce, $customerId);
            } else {
                // Monthly subscription.
                return $this->subscriptionCreate($payment, $request->payment_method_nonce, $customerId);
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
    public function store($payment, $transaction, $customerId)
    {
        Subscription::create([
            'user_id' => auth()->user()->id,
            'payment_id' => $payment->id,
            'transaction_id' => $transaction->id,
            'btree_customer_id' => $customerId,
            'next_billing_at' => $this->calculateDate($payment, $transaction)
        ]);
    }

    /**
     * Functionality to cancel the subscription.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function cancelSubscription($id)
    {
        $result = $this->gateway()->subscription()->cancel($id);

        if ($result->success) {
            auth()->user()->subscription()->update([
                'cancelled_at' => Carbon::now(),
                'ends_at' => $result->subscription->billingPeriodEndDate
            ]);
        }
        return redirect('/home')->with('message', 'Subscription cancelled!');
    }
}
