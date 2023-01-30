@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Subscriptions</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="row row-cols-1 row-cols-md-2 mb-2 text-center">
                            @forelse ($payments as $payment)
                                <div class="col">
                                    <div class="card mb-6 rounded-3 shadow-sm border-primary">
                                        <div class="card-header py-3 text-bg-primary border-primary">
                                            <h4 class="my-0 fw-normal">{{ $payment->plan }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <h1 class="card-title pricing-card-title">{{ $payment->amount }}</h1>
                                            <button type="button" class="w-100 btn btn-lg btn-primary">Subscribe</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p>Not found</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
