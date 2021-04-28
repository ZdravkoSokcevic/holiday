<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    //

    public function load(Request $r)
    {
    	return view('settings');
    }
}
