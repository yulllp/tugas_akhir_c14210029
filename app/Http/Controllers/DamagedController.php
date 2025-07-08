<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DamagedController extends Controller
{
    public function index()
    {
        return view('damaged.index', [
            'title' => 'Barang Rusak',
        ]);
    }
}
