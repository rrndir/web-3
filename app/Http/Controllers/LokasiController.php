<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        return view('v_lokasi.index');
    }
}
