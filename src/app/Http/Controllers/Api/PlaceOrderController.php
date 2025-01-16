<?php

namespace App\Http\Controllers\Api;

use App\Classes\Receipt;
use App\Classes\ServiceMode;
use Illuminate\Support\Facades\DB;
use poster\src\PosterApi;
use Telegram\Bot\Api;

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
        $posterProducts = collect($products)->map(function($item) use ($data) {
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
                $telegram = new Api(env('TELEGRAM_BOT_TEST_TOKEN'));

                $telegram->sendMessage([
                    'parse_mode' => 'html',
                    'chat_id' => env('TELEGRAM_BOT_TEST_CHAT_ID'),
                    'text' => $this->generateReceipt($data['cartData'], $deliveryMethod, $paymentMethod, $data),
                ]);
                return 'success';
            }

        }else {
            return 'error';
        }
    }

    public function generateReceipt($cart, $shippingMethod, $paymentMethod, $data) {

        $receipt = new Receipt();
        $cartIds = collect($data['cartData'])->map(function($item, $key) {
            return $key;
        });
        $products = DB::table('products')->whereIn('id', $cartIds)->get();
        $receiptProducts = collect($products)->map(function($item) use($data) {
            $cartProduct = collect($data['cartData'])->first(function($product, $key) use ($item) {
                if($key == $item->id){
                    return $product;
                }
            });
                return [
                    'name' => $item->name,
                    'count' => $cartProduct['count'],
                    'price' => $cartProduct['price'],
                ];
        });
        $receipt->field("Ім'я", $data['firstName'] ?? null)
            ->field('Прізвище', $data['lastName'] ?? null)
            ->field('Телефон', $data['phone'])
            ->field('Спосіб доставки', $shippingMethod->name)
            ->field('Адрес', $data['address'])
            ->field('Спосіб оплати', $paymentMethod->name)
            ->field('Решта', $data['change'] ?? null)
            ->field('Кількість людей', $data['peopleCount'] ?? null)
            ->field('Коментар', $data['comment'] ?? null)
            ->newLine()
            ->b('Продукти')
            ->newLine()
            ->map($receiptProducts, function($item) {
                $this->product(
                    htmlspecialchars($item['name']),
                    htmlspecialchars($item['count'])
                )->newLine();
            })
        ->newLine()
        ->field('Сума ', $receiptProducts->reduce(function($acc, $item) {
            return $acc + $item['price'] * $item['count'];
        }, 0));
        return $receipt->getText();
    }

}
