@extends('layouts.app')

@push('styles')
<style>
    .form-radio-input-field {
        margin-top: 0.1em;
    }
</style>
@endpush

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Subscriptions</div>

                    <div class="card-body">
                        @if (session('message'))
                            <div class="alert alert-success" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif

                        @if (count($errors) > 0)
                            <div class="alert alert-danger" role="alert">
                                <ul>
                                    @foreach ( $errors->all() as $error )
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="post" id="payment-form" action="{{ url('/checkout') }}">
                            @csrf
                            <div class="row row-cols-1 row-cols-md-2 mb-2 text-center">
                                @forelse ($payments as $payment)
                                    @isset($subscription)
                                        @if ($payment->id === $subscription->payment_id)
                                            @php
                                                $borderPrimary = "border-primary";
                                                $textPrimary = "text-bg-primary";
                                                $checked = "checked";
                                                $disabled = "";
                                                if ($payment->plan !== $paymentYearlyType) {
                                                    if ($subscription->cancelled_at && $subscription->ends_at)
                                                    {
                                                        $borderPrimary = "border-warning";
                                                        $textPrimary = "text-bg-warning";
                                                        $cancelSubscription = '<span class="badge bg-secondary">Subscription Cancelled</span>';
                                                    } else {
                                                        $cancelSubscription = '<a href="/cancel/subscription/'. $subscription->transaction_id.'" class="btn btn-info btn-sm">Cancel Subscription</a>';
                                                    }
                                                }
                                            @endphp
                                        @else
                                            @php
                                                $borderPrimary = "border-default";
                                                $textPrimary = "text-bg-default";
                                                $checked = "";
                                                $disabled = "disabled";
                                                $cancelSubscription = "";
                                            @endphp
                                        @endif
                                    @endisset
                                    <div class="col">
                                        <div class="card mb-6 rounded-3 shadow-sm {{ $borderPrimary ?? '' }}">
                                            <div class="card-header py-3 {{ $textPrimary ?? '' }}
                                            {{ $borderPrimary ?? ''}}">
                                                <h4 class="my-0 fw-normal">{{ $payment->plan }}</h4>
                                            </div>
                                            <div class="card-body">

                                                <h6 class="card-title pricing-card-title amount-wrapper">
                                                    {{ $payment->amount }}
                                                    <input class="form-check-input form-radio-input-field" type="radio"
                                                    value="{{ $payment->id }}" id="id" name="id"
                                                    {{ $checked ?? '' }} {{ $disabled ?? '' }}>
                                                </h6>

                                                {!! $cancelSubscription ?? '' !!}
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <p>Not found</p>
                                @endforelse
                            </div>

                            <div class="row">
                                <div class="col mb-12">
                                    <div class="bt-drop-in-wrapper">
                                        <div id="bt-dropin"></div>
                                    </div>

                                    <input id="nonce" name="payment_method_nonce" type="hidden" />
                                    <button class="button" type="submit"><span>Checkout</span></button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://js.braintreegateway.com/web/dropin/1.33.7/js/dropin.min.js"></script>
    <script>
        var form = document.querySelector('#payment-form');
        var client_token = "{{ $token }}";

        braintree.dropin.create({
            authorization: client_token,
            selector: '#bt-dropin',
            paypal: {
                flow: 'vault'
            }
        }, function(createErr, instance) {
            if (createErr) {
                console.log('Create Error', createErr);
                return;
            }
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                instance.requestPaymentMethod(function(err, payload) {
                    if (err) {
                        console.log('Request Payment Method Error', err);
                        return;
                    }

                    // Add the nonce to the form and submit
                    document.querySelector('#nonce').value = payload.nonce;
                    form.submit();
                });
            });
        });
    </script>
@endpush
