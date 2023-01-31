<?php

namespace App\Http\Traits;

trait CredentialsTrait {
    /**
     * Environment variables.
     */
    public function gateway()
    {
        return new \Braintree\Gateway([
            'environment' => env('BTREE_ENVIRONMENT'),
            'merchantId' => env('BTREE_MERCHANT_ID'),
            'publicKey' => env('BTREE_PUBLIC_KEY'),
            'privateKey' => env('BTREE_PRIVATE_KEY')
        ]);
    }
}
?>