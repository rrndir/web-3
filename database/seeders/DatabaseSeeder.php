<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kategori;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'nama' => 'Administrator',
            'email' => 'admin@gmail.com',
            'role' => '1',
            'status' => 1,
            'hp' => '0812345678901',
            'password' => bcrypt('P@55word'),
        ]);
        User::updateOrCreate([
            'email' => 'aji@gmail.com',
        ], [
            'nama' => 'Aji',
            'email' => 'aji@gmail.com',
            'role' => '0',
            'status' => 0,
            'hp' => '0812345678902',
            'password' => bcrypt('P@55word'),
        ]);

        Kategori::whereIn('nama_kategori', ['Brownies', 'Combro', 'Dawet', 'Mochi', 'Wingko'])->delete();

        $categories = [
            'Action Adventure',
            'Action RPG',
            'Open World',
            'RPG',
            'Sports',
        ];

        foreach ($categories as $categoryName) {
            Kategori::updateOrCreate(['nama_kategori' => $categoryName]);
        }

        $this->call(GameCatalogSeeder::class);
    }
}
