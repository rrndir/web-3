<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', '1')->first();
        if (!$admin) {
            $this->command->error('Admin user is required before seeding the game catalog.');
            return;
        }

        $categoryIds = Kategori::pluck('id', 'nama_kategori');
        $imageDirectory = public_path('storage/img-produk');
        if (!is_dir($imageDirectory)) {
            mkdir($imageDirectory, 0755, true);
        }

        $games = [
            [
                'nama_produk' => 'Red Dead Redemption 2',
                'kategori' => 'Open World',
                'harga' => 879000,
                'foto' => 'red-dead-redemption-2.jpg',
                'detail' => '<p>Red Dead Redemption 2 adalah petualangan aksi-open world epik dari Rockstar Games.</p><p>Menjelajahi Amerika pada tahun 1899, membangun kelompok, bertahan hidup, dan menentukan jalan Arthur Morgan dalam dunia Barat yang luas.</p>',
            ],
            [
                'nama_produk' => 'Black Myth: Wukong',
                'kategori' => 'Action RPG',
                'harga' => 699999,
                'foto' => 'black-myth-wukong.jpg',
                'detail' => '<p>Black Myth: Wukong adalah action RPG berlatar mitologi Tiongkok.</p><p>Menjalankan Perjalanan ke Barat sebagai Destined One dengan pertarungan yang cepat, visual dramatis, dan kemampuan transformasi.</p>',
            ],
            [
                'nama_produk' => 'ELDEN RING',
                'kategori' => 'RPG',
                'harga' => 599000,
                'foto' => 'elden-ring.jpg',
                'detail' => '<p>ELDEN RING menghadirkan fantasy action RPG dengan dunia terbuka yang luas dari FromSoftware.</p><p>Telusuri Lands Between, bangun karakter, gunakan berbagai senjata dan sihir, serta taklukkan musuh legendaris.</p>',
            ],
            [
                'nama_produk' => 'Grand Theft Auto V Enhanced',
                'kategori' => 'Action Adventure',
                'harga' => 439000,
                'foto' => 'gta-v-enhanced.jpg',
                'detail' => '<p>Grand Theft Auto V Enhanced menghadirkan Los Santos dalam kualitas yang ditingkatkan.</p><p>Ikuti kisah tiga karakter unik dan nikmati mode GTA Online dengan visual serta performa modern.</p>',
            ],
            [
                'nama_produk' => 'Cyberpunk 2077',
                'kategori' => 'RPG',
                'harga' => 699999,
                'foto' => 'cyberpunk-2077.jpg',
                'detail' => '<p>Cyberpunk 2077 adalah RPG open-world berlatar Night City.</p><p>Bermain sebagai V, seorang mercenary dengan implantasi unik dan pilihan alur cerita yang menentukan nasib kota neon yang penuh konflik.</p>',
            ],
            [
                'nama_produk' => 'EA SPORTS FC 26',
                'kategori' => 'Sports',
                'harga' => 239000,
                'foto' => 'ea-sports-fc-26.jpg',
                'detail' => '<p>EA SPORTS FC 26 menghadirkan pengalaman sepak bola modern dari EA Sports.</p><p>Mainkan klub favorit, kelola Ultimate Team, dan nikmati mode kompetitif dengan gameplay dan animasi terbaru.</p>',
            ],
        ];

        foreach ($games as $game) {
            $source = public_path('frontend/img/game/' . $game['foto']);
            if (is_file($source)) {
                foreach ([
                    $game['foto'],
                    'thumb_lg_' . $game['foto'],
                    'thumb_md_' . $game['foto'],
                    'thumb_sm_' . $game['foto'],
                ] as $fileName) {
                    copy($source, $imageDirectory . '/' . $fileName);
                }
            }

            Produk::updateOrCreate(
                ['nama_produk' => $game['nama_produk']],
                [
                    'kategori_id' => $categoryIds[$game['kategori']],
                    'user_id' => $admin->id,
                    'status' => 1,
                    'detail' => $game['detail'],
                    'harga' => $game['harga'],
                    'stok' => 100,
                    'berat' => 0,
                    'foto' => $game['foto'],
                ]
            );
        }
    }
}
