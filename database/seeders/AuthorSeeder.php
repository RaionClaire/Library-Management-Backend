<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            // KLASIK
            ['name' => 'Leo Tolstoy', 'bio' => 'Novelis Rusia era klasik, pengarang War and Peace & Anna Karenina.'],
            ['name' => 'Jane Austen', 'bio' => 'Penulis Inggris, tajam mengulas tatanan sosial dalam Pride and Prejudice.'],
            ['name' => 'Pramoedya Ananta Toer', 'bio' => 'Sastrawan Indonesia, Tetralogi Buru yang monumental.'],
            ['name' => 'Charles Dickens', 'bio' => 'Potret sosial Inggris era Victoria; Oliver Twist, Great Expectations.'],
            ['name' => 'Fyodor Dostoevsky', 'bio' => 'Eksplorasi jiwa manusia; Crime and Punishment, The Brothers Karamazov.'],

            // SEJARAH
            ['name' => 'Yuval Noah Harari', 'bio' => 'Sejarawan, esai populer tentang sejarah & masa depan kemanusiaan.'],
            ['name' => 'Peter Frankopan', 'bio' => 'Sejarah global & Jalur Sutra, menata ulang narasi pusat-periferi.'],
            ['name' => 'Antony Beevor', 'bio' => 'Sejarah militer modern: Stalingrad, D-Day, Berlin.'],
            ['name' => 'Rick Atkinson', 'bio' => 'Trilogi Perang Dunia II, riset detail & narasi kuat.'],
            ['name' => 'Taufik Abdullah', 'bio' => 'Sejarawan Indonesia; kebudayaan & masyarakat Nusantara.'],

            // FILSAFAT
            ['name' => 'Friedrich Nietzsche', 'bio' => 'Filsafat kritik moral & budaya; Zarathustra, Genealogy of Morals.'],
            ['name' => 'Albert Camus', 'bio' => 'Filsafat absurdis; The Myth of Sisyphus, The Stranger.'],
            ['name' => 'Plato', 'bio' => 'Dialog-dialog fondasional: Republik, Phaedo, Symposium.'],
            ['name' => 'Aristoteles', 'bio' => 'Etika, logika, metafisika—pondasi ilmu Barat.'],
            ['name' => 'Søren Kierkegaard', 'bio' => 'Eksistensialisme awal; lompatan iman & subjektivitas.'],

            // FIKSI
            ['name' => 'Andrea Hirata', 'bio' => 'Penulis Indonesia; Laskar Pelangi & kisah-kisah inspiratif.'],
            ['name' => 'Haruki Murakami', 'bio' => 'Gaya surealis, kesepian modern, dan musik; Kafka on the Shore.'],
            ['name' => 'Eka Kurniawan', 'bio' => 'Realismo magis Indonesia; Lelaki Harimau, Cantik Itu Luka.'],
            ['name' => 'Ahmad Tohari', 'bio' => 'Kemanusiaan desa & moralitas; Ronggeng Dukuh Paruk.'],
            ['name' => 'Chimamanda Ngozi Adichie', 'bio' => 'Identitas & diaspora; Americanah, Half of a Yellow Sun.'],

            // FANTASI
            ['name' => 'J.R.R. Tolkien', 'bio' => 'Arsitek Middle-earth; The Hobbit, The Lord of the Rings.'],
            ['name' => 'Brandon Sanderson', 'bio' => 'Cosmere, sistem sihir presisi; Mistborn, Stormlight.'],
            ['name' => 'Neil Gaiman', 'bio' => 'Dongeng gelap & mitologi modern; American Gods, Stardust.'],
            ['name' => 'Leigh Bardugo', 'bio' => 'Grishaverse: Shadow and Bone, Six of Crows.'],
            ['name' => 'Patrick Rothfuss', 'bio' => 'The Kingkiller Chronicle; musik, nama, dan legenda.'],

            // NONFIKSI
            ['name' => 'Malcolm Gladwell', 'bio' => 'Psikologi populer & anomali sosial; Outliers, Blink.'],
            ['name' => 'Atul Gawande', 'bio' => 'Bedah & etika medis; Being Mortal, Complications.'],
            ['name' => 'Michael Pollan', 'bio' => 'Makanan, tanaman, & kebudayaan; Omnivore’s Dilemma.'],
            ['name' => 'B.J. Fogg', 'bio' => 'Perilaku & kebiasaan mikro; Tiny Habits.'],
            ['name' => 'Rhenald Kasali', 'bio' => 'Transformasi & kepemimpinan; Manajemen perubahan.'],

            // ROMANSA
            ['name' => 'Jojo Moyes', 'bio' => 'Romansa emosional modern; Me Before You.'],
            ['name' => 'Nicholas Sparks', 'bio' => 'Cinta & harapan; The Notebook, A Walk to Remember.'],
            ['name' => 'Ilana Tan', 'bio' => 'Musim-musim cinta; Autumn in Paris, Winter in Tokyo.'],
            ['name' => 'Tere Liye', 'bio' => 'Lintas genre; romansa, petualangan, refleksi.'],
            ['name' => 'Rainbow Rowell', 'bio' => 'Coming-of-age & romansa hangat; Eleanor & Park.'],

            // FIKSI SAINS
            ['name' => 'Isaac Asimov', 'bio' => 'Foundation, Robot—fiksi sains keras & ide besar.'],
            ['name' => 'Arthur C. Clarke', 'bio' => '2001: A Space Odyssey; visi kosmis & sains.'],
            ['name' => 'Liu Cixin', 'bio' => 'Trisolaris; skala kosmik & fisika spekulatif.'],
            ['name' => 'Philip K. Dick', 'bio' => 'Realitas gentar & identitas; Do Androids Dream…'],
            ['name' => 'Andy Weir', 'bio' => 'Hard-SF survival; The Martian, Project Hail Mary.'],

            // KOMIK
            ['name' => 'Hajime Isayama', 'bio' => 'Attack on Titan—epik gelap, politik & survival.'],
            ['name' => 'Eiichiro Oda', 'bio' => 'One Piece—petualangan, persahabatan, dan kebebasan.'],
            ['name' => 'Yoshihiro Togashi', 'bio' => 'Hunter x Hunter—strategi & dunia kompleks.'],
            ['name' => 'Frank Miller', 'bio' => 'Batman: Year One, 300—gaya noir & grit.'],
            ['name' => 'Alan Moore', 'bio' => 'Watchmen, V for Vendetta—struktur narasi revolusioner.'],
        ];

        $rows = [];
        foreach ($authors as $a) {
            $rows[] = [
                'name' => $a['name'],
                'biography' => $a['bio'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('authors')->insert($rows);
    }
}
