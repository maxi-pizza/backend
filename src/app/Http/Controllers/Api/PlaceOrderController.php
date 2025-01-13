<?php

namespace App\Http\Controllers\Api;

use App\Classes\ServiceMode;
use Illuminate\Support\Facades\DB;
use poster\src\PosterApi;


class PlaceOrderController
{

    public function index(){
        $response = file_get_contents('php://input');
        $data = json_decode($response, true);

        $spot = DB::table('spots')->first('poster_token');
        $deliveryMethod = DB::table('delivery')->where('id', $data['deliveryMethod'])->first();
        $paymentMethod = DB::table('payment')->where('id', $data['paymentMethod'])->first();

        PosterApi::init([
            'access_token' => $spot->poster_token,
        ]);

//        DB::table('products')->where('id', $data['cartData']->map(function ($value) {
//            return $value;
//        }))->get();
        $cartIds = collect($data['cartData'])->map(function($item, $key) {
            return $key;
        });
        $products = DB::table('products')->whereIn('id', $cartIds)->get();
        $posterProducts = collect($products)->map(function($item) use ($cartIds, $data) {
            $cartProduct = collect($data['cartData'])->first(function($product, $key) use ($item) {
                if($key == $item->id) {
                    return $product;
                }
            });

            return [
                'count' => $cartProduct['count'],
                'product_id' => $item->poster_id,
            ];
        });

        if($spot) {
            $comment = collect([
                ['comment', $data['comment']],
                ['решта', $data['change']],
                ['способ оплати', $paymentMethod->name],
                ['sticks', $data['peopleCount']]
            ])->filter(function ($part) {
                return !empty($part[1]);
            })->map(function ($part) {
                    return ($part[0] ? $part[0] . ': ' : '') . $part[1];
                })->join(' || ');

            $order = [
                'spot_id' => '1',
                'comment' => $comment,
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'phone' => $data['phone'],
                'products' => $posterProducts,
                'service_mode' => ServiceMode::DELIVERY,
            ];

            if($deliveryMethod->id == ServiceMode::DELIVERY) {
                $order['service_mode'] = ServiceMode::DELIVERY;
                $order['address'] = $data['address'] ?? null;
            }else {
                $order['service_mode'] = ServiceMode::TAKEAWAY;
            }

            $posterResult = (object)PosterApi::incomingOrders()->createIncomingOrder($order);
            if(isset($posterResult->error)) {
                return $posterResult->error;
            }else {
                return 'ok';
            }

        }else {
            return 'error';
        }
    }

}
