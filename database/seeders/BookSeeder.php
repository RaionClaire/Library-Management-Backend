<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = [
            'Klasik','Sejarah','Filsafat','Fiksi','Fantasi','Nonfiksi','Romansa','Fiksi Sains','Komik'
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $id = DB::table('categories')->where('name', $name)->value('id');
            if (!$id) {
                throw new \RuntimeException("Kategori '{$name}' tidak ditemukan. Pastikan BookCategorySeeder sudah dijalankan.");
            }
            $categories[$name] = $id;
        }

        $authorMap = [
            'Klasik' => DB::table('authors')->whereIn('name', ['Leo Tolstoy','Jane Austen','Pramoedya Ananta Toer','Charles Dickens','Fyodor Dostoevsky'])->pluck('id')->all(),
            'Sejarah' => DB::table('authors')->whereIn('name', ['Yuval Noah Harari','Peter Frankopan','Antony Beevor','Rick Atkinson','Taufik Abdullah'])->pluck('id')->all(),
            'Filsafat' => DB::table('authors')->whereIn('name', ['Friedrich Nietzsche','Albert Camus','Plato','Aristoteles','Søren Kierkegaard'])->pluck('id')->all(),
            'Fiksi' => DB::table('authors')->whereIn('name', ['Andrea Hirata','Haruki Murakami','Eka Kurniawan','Ahmad Tohari','Chimamanda Ngozi Adichie'])->pluck('id')->all(),
            'Fantasi' => DB::table('authors')->whereIn('name', ['J.R.R. Tolkien','Brandon Sanderson','Neil Gaiman','Leigh Bardugo','Patrick Rothfuss'])->pluck('id')->all(),
            'Nonfiksi' => DB::table('authors')->whereIn('name', ['Malcolm Gladwell','Atul Gawande','Michael Pollan','B.J. Fogg','Rhenald Kasali'])->pluck('id')->all(),
            'Romansa' => DB::table('authors')->whereIn('name', ['Jojo Moyes','Nicholas Sparks','Ilana Tan','Tere Liye','Rainbow Rowell'])->pluck('id')->all(),
            'Fiksi Sains' => DB::table('authors')->whereIn('name', ['Isaac Asimov','Arthur C. Clarke','Liu Cixin','Philip K. Dick','Andy Weir'])->pluck('id')->all(),
            'Komik' => DB::table('authors')->whereIn('name', ['Hajime Isayama','Eiichiro Oda','Yoshihiro Togashi','Frank Miller','Alan Moore'])->pluck('id')->all(),
        ];

        $quota = [
            'Klasik' => 12,
            'Sejarah' => 12,
            'Filsafat' => 12,
            'Fiksi' => 14,
            'Fantasi' => 14,
            'Nonfiksi' => 12,
            'Romansa' => 12,
            'Fiksi Sains' => 12,
            'Komik' => 10,
        ];

        $publishers = [
            'Gramedia', 'Bentang', 'Mizan', 'Kanisius', 'KPG', 'Penguin', 'HarperCollins', 'Vintage', 'Orbit', 'Tor'
        ];

        $motifs = [
            'Klasik' => ['Bayang Kota','Musim di Taman','Pada Senja','Lukisan Lama','Surat Tak Terkirim','Langkah Sunyi'],
            'Sejarah' => ['Lintasan Imperium','Di Balik Arsip','Jejak Peradaban','Bentang Nusantara','Zaman Api','Peta Jalur Sutra'],
            'Filsafat' => ['Tentang Ada','Etika Sehari-hari','Paralogika','Dialog di Serambi','Mitos & Makna','Sketsa Eksistensi'],
            'Fiksi' => ['Kamar Paling Sunyi','Hujan di Bulan Juni','Di Stasiun Kota','Orkes Malam','Laut yang Diam','Kopi & Kisah'],
            'Fantasi' => ['Menara Awan','Nama Angin','Sang Penjaga Rimba','Sungai Bintang','Gerbang Emberlith','Balada Para Penenun'],
            'Nonfiksi' => ['Atom Kebiasaan','Cara Berpikir Cepat','Rasa & Akal','Manusia & Mesin','Catatan Klinik','Disrupsi & Adaptasi'],
            'Romansa' => ['Janji di Peron','Purnama untuk Kita','Musim yang Kau Tinggal','Surat Biru','Langkah Berdua','Bahasa Tatapan'],
            'Fiksi Sains' => ['Orbit Ketiga','Algoritma Hati','Kapsul Waktu','Koloni di Titan','Protokol Aurora','Paradoks Kupu-kupu'],
            'Komik' => ['Legenda Timur','Petualang Laut','Arena Hunter','Malam di Gotham','Jam Pasir','Topeng di Balik Kota'],
        ];

        $yearRange = [1980, (int)date('Y')];
        $rows = [];
        $isbnCounter = 10001;

        $makeIsbn = function() use (&$isbnCounter) {
            $seq = str_pad((string)$isbnCounter++, 5, '0', STR_PAD_LEFT);
            return "978-622-".mt_rand(10,99)."-".$seq;
        };

        $pickAuthor = function(array $ids, int $i) {
            return $ids[$i % max(count($ids), 1)];
        };

        foreach ($quota as $catName => $count) {
            $catId = $categories[$catName];
            $authorIds = $authorMap[$catName];
            $motif = $motifs[$catName];

            for ($i = 1; $i <= $count; $i++) {
                $titleBase = $motif[($i-1) % count($motif)];
                $title = $titleBase.' #'.str_pad((string)$i, 2, '0', STR_PAD_LEFT);

                $rows[] = [
                    'category_id' => $catId,
                    'author_id'   => $pickAuthor($authorIds, $i-1),
                    'title'       => $title,
                    'isbn'        => $makeIsbn(),
                    'publisher'   => $publishers[array_rand($publishers)],
                    'year'        => mt_rand($yearRange[0], $yearRange[1]),
                    'stock'       => mt_rand(2, 24),
                    'cover_url'   => null,   
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('books')->insert($chunk);
        }
    }
}
