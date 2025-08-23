<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Payment;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{

    public function index()
    {

        $result = [];
        $categories = Category::query()
            ->where('hidden', false)
            ->where('parent_id', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        $products = Product::with(['images'])
            ->where('hidden', false)
            ->orderBy('sort_order','asc')
            ->get();

        $product_categories = DB::table('product_categories')->get();
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'products' => [],
            ];
        }
        foreach($products as $product) {
            $product_category = $product_categories->first(function($value) use($product) {
                return $value->product_id == $product->id;
            });

            if($product_category) {
                $category = $categories->first(function($value) use ($product_category) {
                 return $value->id == $product_category->category_id;
                });

                if($category) {
                    foreach($result as &$value) {
                            if(isset($value['slug']) && $value['slug'] === $category->slug) {
                                $value['products'][] = $product;
                            }
                    }
                    unset($value);
                }
            }

        }

        $banners = Slider::where('hidden', false)->get();
        $shippingMethods = Delivery::where('hidden', false)->get();
        $paymentMethods = Payment::where('hidden', false)->get();
        return [
            'categories' => $result,
            'banners' => $banners,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
        ];

    }

    public function images(){
        $products = Product::with('images')->get();

        $result = [];
        foreach($products as $product) {
            $ingredients = [];
            foreach ($product->attributes as $productAttribute) {


                foreach ($productAttribute->attributeValues as $key => $attributeValue) {

                    if ($attributeValue->attribute_id == 3){

                        $ingredients[] = $attributeValue->value;
                    }

                }

            }

            $additional_photo = $product->images()->first()['full'];
            $img = $additional_photo ? 'https://emojisushi.com.ua/storage/' . $additional_photo : null ;
            $result[] = [
                'poster_id' => $product['poster_id'],
                'name' => $product['name'],
                'ingredients' => implode(', ', $ingredients),
                'image'     =>  $img,
            ];
        }
        echo json_encode($result);
    }

}
