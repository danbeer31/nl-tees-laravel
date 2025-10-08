<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Return a Blade view named 'home'
        return view('home');
    }
    public function test()
    {
        // Return a Blade view named 'home'
        return view('home.test');
    }

}
