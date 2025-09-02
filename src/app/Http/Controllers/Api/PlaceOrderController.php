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

        $deliveryMethod = DB::table('delivery')->where('id', $data['delivery_method_id'])->first();
        $paymentMethod = DB::table('payment')->where('id', $data['payment_method_id'])->first();

        PosterApi::init([
            'access_token' => env('POSTER_ACCESS_TOKEN')
        ]);

        $cartProductIds = collect($data['products'])->map(function($item) {
            return $item['product_id'];
        });
        $products = DB::table('products')->whereIn('id', $cartProductIds)->get();
        $posterProducts = collect($products)->map(function($item) use ($data) {
            $cartProduct = collect($data['products'])->first(function($product) use ($item) {
               return $product['product_id'] == $item->id;
            });

            return [
                'count' => $cartProduct['count'],
                'product_id' => $item->poster_id,
            ];
        });

        $comment = collect([
            ['Комментар', $data['comment']],
            ['Решта', $data['change']],
            ['Спосіб оплати', $paymentMethod->name],
            ['Кількість персон', $data['people_count']]
        ])->filter(function ($part) {
            return !empty($part[1]);
        })->map(function ($part) {
            return ($part[0] ? $part[0] . ': ' : '') . $part[1];
        })->join(' || ');

        $name = $data['name'];

        $firstName = explode(' ', $name)[0];
        $lastName = explode(' ', $name)[1] ?? null;
        $email = $data['email'] ?? null;
        $phone = $data['phone'];

        $order = [
            'spot_id' => '1',
            'comment' => $comment,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'products' => $posterProducts,
            // test products
//            'products' => [
//               [
//                   'product_id' => 5,
//                   'count' => 1,
//               ]
//            ]
        ];
         // todo: create order
//        $order = Order::create([
//            'email' => $data['email'],
//            'first_name' => $data['name'],
//            'phone' => $data['phone'],
//            'address' => $data['address'],
//            'comment' => $comment,
//            'payment_id' => $data['payment_id'],
//            'delivery_id' => $data['delivery_id'],
//            'sum'         => Cart::getTotal()
//        ]);

        if($deliveryMethod->id == ServiceMode::DELIVERY) {
            $order['service_mode'] = ServiceMode::DELIVERY;
            $order['address'] = $data['address'] ?? null;
        }else {
            $order['service_mode'] = ServiceMode::TAKEAWAY;
        }

        $posterResult = (object)PosterApi::incomingOrders()->createIncomingOrder($order);
        if(isset($posterResult->error)) {
            throw new \RuntimeException($posterResult->message);
        } else {
            $bot_token = env('TELEGRAM_BOT_ID');
            $chat_id = env('TELEGRAM_CHAT_ID');
            $telegram = new Api($bot_token);

            $telegram->sendMessage([
                'parse_mode' => 'html',
                'chat_id' => $chat_id,
                'text' => $this->generateReceipt($deliveryMethod, $paymentMethod, $data),
            ]);
            return 'success';
        }
    }

    public function generateReceipt($shippingMethod, $paymentMethod, $data) {

        $receipt = new Receipt();
        $cartIds = collect($data['products'])->map(function($item) {
            return $item['product_id'];
        });
        $products = DB::table('products')->whereIn('id', $cartIds)->get();
        $receiptProducts = collect($products)->map(function($item) use($data) {
            $cartProduct = collect($data['products'])->first(function($product) use ($item) {
               return $product['product_id'] == $item->id;
            });
                return [
                    'name' => $item->name,
                    'count' => $cartProduct['count'],
                    'price' => $item->price
                ];
        });
        $name = $data['name'];
        $firstName = explode(' ', $name)[0] ?? null;
        $lastName = explode(' ', $name)[1] ?? null;

        $receipt->field("Ім'я", $firstName)
            ->field('Прізвище', $lastName)
            ->field('Телефон', $data['phone'])
            ->field('Спосіб доставки', $shippingMethod->name)
            ->field('Адрес', $data['address'] ?? null)
            ->field('Email', $data['email'] ?? null)
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
