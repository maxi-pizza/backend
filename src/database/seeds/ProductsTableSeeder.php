<?php
use App\Libraries\Poster;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        function delEndind($str){
            if(!empty($str)){
                $sub_str = substr($str, 0, strlen($str)-2);
                if($sub_str === false){
                    return 0;
                }
                return $sub_str;
            }

            return 0;
        }

        $spots = DB::table('spots')->get();

        foreach($spots as $spot){
            $poster = new Poster($spot->poster_token);
            $data = json_decode($poster->query('menu.getProducts'), true);


            foreach($data['response'] as $value) {

                AttributeValue::create([
                    'attribute_id'  => 3,
                    'value'         => $value['product_name'],
                    'poster_id'     => $value['product_id'],
                ]);

                $record = [
                    'name'          => $value['product_name'],
                    'poster_id'     => $value['product_id'],
                    'weight'        => $value['out'],
                    'image'         => $value['photo'] ? $spot->poster_link . $value['photo'] : null,
                    'unit'          => 'г',
                    'sort_order'    => $value['sort_order'],
                ];



                if (isset($value['modifications'])) {
                    $attribute_id = Attribute::insertGetId([
                        'name'          => $value['product_name'] . "_modificator",
                        'code'          => str_slug($value['product_name'] . "_modificator" ),
                        'frontend_type' => 'radio',
                        'is_filterable' => 1
                    ]);


                    $product = Product::create($record);

                    $category = Category::wherePosterId($value['menu_category_id'])->first();

                    if(!empty($category)){
                        $product->categories()->attach($category['id']);
                    }



                    foreach ($value['modifications'] as $value1) {

                        $attribute_value_id = AttributeValue::insertGetId([
                            'attribute_id' => $attribute_id,
                            'value'        => $value1['modificator_name'],
                            'poster_id'    => $value1['modificator_id'],
                            'price'        => delEndind($value1['spots'][0]['price'])
                        ]);

                        $product_attribute = ProductAttribute::create([
                            'price'         => delEndind($value1['spots'][0]['price']),
                            'product_id'    => $product->id
                        ]);

                        $product_attribute->attributeValues()->attach($attribute_value_id);
                    }

                } else {
                    $record['price'] = delEndind($value['price'][1]);
                    $product = Product::create($record);
                    $category = Category::wherePosterId($value['menu_category_id'])->first();
                    if(!empty($category)){
                        $product->categories()->sync([$category['id']]);
                    }

                    if(isset($value['ingredients'])){

                        // Перебираю массив ингредиентов
                        foreach($value['ingredients'] as $ingredient){

                            if(preg_match('/Бокс д\/суши|Набор ролл|Губка д\/посуды|Дезинфектор АХД 2000|Держатели д\/палочек|Ершик д\/посуды уп|Заправка для риса/',$ingredient['ingredient_name'])) continue;

                            $ingredient_name = preg_replace('/^Ролл|^ролл|ПФ$|пф$|Пф$|филе на шкуре|31\/40$/','',$ingredient['ingredient_name']);
                            // Создаю ингридиент и привязываю его к аттрибуты "Ингридиент"
                            if(AttributeValue::where('poster_id', '=', $ingredient['ingredient_id'])->exists()){
                                $attribute_value_id = AttributeValue::where('poster_id', '=', $ingredient['ingredient_id'])->first();
                            }else{
                                $attribute_value_id = AttributeValue::insertGetId([
                                    'attribute_id' => 3,// 3 - id аттрибута "Ингридиент"
                                    'value'        => $ingredient_name,
                                    'poster_id'    => $ingredient['ingredient_id']
                                ]);
                            }


                            $such_product_attribute = AttributeValue::where("poster_id", "=", $ingredient['ingredient_id'])
                                ->first()
                                ->productAttributes()
                                ->where('product_id', '=', $product->id)
                                ->first();

                            if(!isset($such_product_attribute)){
                                // Создаю аттрибут для продукта
                                $product_attribute = ProductAttribute::create([
                                    'product_id' =>  $product->id
                                ]);

                                // Привязываю созданные выше аттрибут и ингридиет,
                                $product_attribute->attributeValues()->sync($attribute_value_id);
                            }

                        }
                    }
                }
            }
        }

    }
}
