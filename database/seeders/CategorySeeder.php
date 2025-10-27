<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name'        => 'Klasik',
                'description' => 'Karya sastra legendaris lintas zaman yang diakui secara universal.',
            ],
            [
                'name'        => 'Sejarah',
                'description' => 'Rekaman peristiwa masa lalu, biografi tokoh, dan perjalanan peradaban.',
            ],
            [
                'name'        => 'Filsafat',
                'description' => 'Eksplorasi tentang pemikiran, makna hidup, dan realitas manusia.',
            ],
            [
                'name'        => 'Fiksi',
                'description' => 'Cerita rekaan realistik—berpusat pada kehidupan dan hubungan manusia.',
            ],
            [
                'name'        => 'Fantasi',
                'description' => 'Dunia imajinatif dengan elemen magis, mitologi, atau dunia alternatif.',
            ],
            [
                'name'        => 'Nonfiksi',
                'description' => 'Berdasarkan fakta nyata — edukatif, dokumenter, atau reflektif.',
            ],
            [
                'name'        => 'Romansa',
                'description' => 'Cerita emosional yang berfokus pada dinamika dan perjalanan cinta.',
            ],
            [
                'name'        => 'Fiksi Sains',
                'description' => 'Eksplorasi masa depan, teknologi, dan kemungkinan ilmiah.',
            ],
            [
                'name'        => 'Komik',
                'description' => 'Cerita visual bergaya grafis — termasuk manga, webtoon, dan graphic novel.',
            ],
        ]);
    }
}
