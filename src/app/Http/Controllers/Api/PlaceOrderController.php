<?php

namespace App\Http\Controllers\Api;

use App\Classes\Receipt;
use App\Classes\ServiceMode;
use App\Models\Order;
use App\Models\OrderProduct;
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

        $posterOrder = [
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

        $sum = $products->reduce(function($acc, $item) use ($data) {
            $cartProduct = collect($data['products'])->first(function($product) use ($item) {
                return $product['product_id'] == $item->id;
            });
            return $acc + $item->price * $cartProduct['count'];
        }, 0);

        $order = Order::create([
            'email' => $data['email'],
            'first_name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'comment' => $comment,
            'payment_id' => $paymentMethod->id,
            'delivery_id' => $deliveryMethod->id,
            'sum'         => $sum
        ]);

        $products->map(function($item) use ($order, $data) {
            $cartProduct = collect($data['products'])->first(function($product) use ($item) {
                return $product['product_id'] == $item->id;
            });

            OrderProduct::create([
                'order_id'              => $order->id,
                'product_id'            => $item->id,
                'quantity'              => $cartProduct['count'],
                'price'                 => $item->price,
                'sum'                   => $item->price * $cartProduct['count']
            ]);
        });


        if($deliveryMethod->id == ServiceMode::DELIVERY) {
            $posterOrder['service_mode'] = ServiceMode::DELIVERY;
            $posterOrder['address'] = $data['address'] ?? null;
        }else {
            $posterOrder['service_mode'] = ServiceMode::TAKEAWAY;
        }

        $posterResult = (object)PosterApi::incomingOrders()->createIncomingOrder($posterOrder);
        if(isset($posterResult->error)) {
            throw new \RuntimeException($posterResult->message);
        }

        $order->is_sent_to_poster = true;
        $order->save();

        // todo: create user

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
