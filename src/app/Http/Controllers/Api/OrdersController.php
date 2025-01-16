<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\Poster;
use App\Models\Spot;
use Illuminate\Support\Facades\DB;


class OrdersController extends Controller
{
    public function send(){


        // Takes raw data from the request
        $json = file_get_contents('php://input');

        // Converts it into a PHP object
        $data = json_decode($json, true);

        $spot = DB::table('spots')->where('slug', $data['spot_slug'])->first();

        if($spot){
            $comment = '';
            if(!empty($data['formData']['change'])){
                $comment .= "Комментарий: ".$data['formData']['comment'];
            }
            $comment .= " || Способ доставки: " . $data['formData']['deliveryMethod'];
            $comment .= " || Способ Оплаты: " .  $data['formData']['paymentMethod'];
            if(!empty($data['formData']['change'])){
                $comment .= " || Приготовить сдачу с: ".$data['formData']['change'];
            }


            $products = [];

            foreach($data['cartData'] as $value){
                $products[] = [
                    'product_id' => $value['poster_id'],
                    'count'      => $value['quantity'],
                    'modificator_id' => $value['modificator_id']
                ];
            }

            $order = [
                'spot_id' => 1,
                'first_name' => $data['formData']['name'],
                'phone'      => $data['formData']['phone'],
                'email'      => $data['formData']['email'],
                'comment'    => $comment,
                'address'    => $data['formData']['address'],
                'products'   => $products
            ];

            $poster = new Poster($spot->poster_token);
            return json_encode($poster->sendOrder($order));

        }else{
            // spot not found
        }




    }
}
