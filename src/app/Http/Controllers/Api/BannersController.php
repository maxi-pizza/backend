<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slider;

class BannersController extends Controller
{
    public function index()
    {
        $banners = Slider::where('hidden', false)->get();

        return $banners;

    }
}
