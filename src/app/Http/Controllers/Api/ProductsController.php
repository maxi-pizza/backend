<?php

namespace App\Http\Controllers\Api;


use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Libraries\Poster;

class ProductsController extends Controller
{

    public function index()
    {

        $result = [];
        $categories = DB::table('categories')->get()
            ->where('hidden', false)
            ->where('parent_id', 1);
        $products = DB::table('products')->get();
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
        return $result;

//        foreach($products as $key => $value) {
//
//            $product = Product::with('ingredients')->where('id', $value->id)->first();
//
//            $filtered = $product['ingredients']->filter(function ($value, $key) {
//                return $value['status'] == 1;
//            });
//
//            //dd($ingredients['ingredients']);
//            $ingredients_string = $filtered->implode( 'name' , ', ');
//            $products[$key]->ingredients = $ingredients_string;
//
//            $image = DB::table('product_images')->where('product_id', $value->id)->value('full');
//            $products[$key]->image = $image;
//            if($products[$key]->status == 0) {
//                unset($products[$key]);
//            }
//        }
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

    public function getImage($posterId){
        $product = Product::with('images')->where('poster_id', $posterId)->first();
    }



}
