<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
class CheckoutController
{

    public function index() {
        $payment_methods = DB::table("payment")->get();
        $delivery_methods = DB::table("delivery")->get();

        $result = [];
        foreach($payment_methods as $payment_method) {
            $result['payment_methods'][] = $payment_method;
        }
        foreach($delivery_methods as $delivery_method) {
            $result['delivery_methods'][] = $delivery_method;
        }

        return $result ;
    }
}
