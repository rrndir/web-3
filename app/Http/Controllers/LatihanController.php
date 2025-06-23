<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LatihanController extends Controller
{
    public function getTabel()
    {
        return view('ambiltabel', [
            'judul' => 'Data Mahasiswa'
        ]);
    }

    public function getForm()
    {
        return view('ambilform', [
            'judul' => 'Tambah Mahasiswa'
        ]);
    }

    public function getFolder()
    {
        return view('latihan.ambilfolder', [
            'judul' => 'Memanggil didalam Folder'
        ]);
    }
}
