<?php

use Illuminate\Database\Seeder;
use App\Models\Spot;

class SpotsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Spot::create([
            'name'          => config('poster.account_name'),
            'poster_token'  => config('poster.access_token'),
            'poster_link'   => config('poster.url')
        ]);

    }
}
