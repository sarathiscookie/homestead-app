<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Payment::insert([
            [
                'plan' => 'Monthly',
                'type' => 'Braintree',
                'amount' => 25,
                'status' => 'enabled'
            ],
            [
                'plan' => 'Yearly',
                'type' => 'Braintree',
                'amount' => 250,
                'status' => 'enabled'
            ]
        ]);
    }
}
