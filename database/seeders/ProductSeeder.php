<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereIn('role', ['super_admin', 'admin_kota'])->first();
        $adminId = $admin?->id;

        $products = [
            [
                'name' => 'Jas Resmi ISNU Nasional',
                'slug' => 'jas-resmi-isnu-nasional',
                'category' => 'seragam',
                'description' => 'Jas resmi Ikatan Sarjana Nahdlatul Ulama warna hijau tua eksklusif dengan furing lembut berkualitas tinggi, kancing berlogo, dan bordir komputer lambang ISNU presisi di saku dada kiri. Sangat elegan untuk acara pelantikan, konferensi, dan resepsi organisasi resmi.',
                'price' => 275000,
                'weight_grams' => 600,
                'stock' => 45,
                'image_path' => 'products/jas_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Batik Premium Khas ISNU Surabaya',
                'slug' => 'batik-premium-khas-isnu-surabaya',
                'category' => 'batik',
                'description' => 'Kemeja batik katun primisima premium motif ornamen khas Nahdlatul Ulama berpadu grafis sarjana kontemporer. Bahan adem, menyerap keringat, tidak luntur, dan jahitan kuat berstandar tailor.',
                'price' => 185000,
                'weight_grams' => 300,
                'stock' => 60,
                'image_path' => 'products/batik_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Kaos Polo Lacoste Bordir ISNU',
                'slug' => 'kaos-polo-lacoste-bordir-isnu',
                'category' => 'kaos',
                'description' => 'Kaos polo katun lacoste CVC 24s dengan bordir komputer logo ISNU di dada kiri dan detail list hijau-emas di kerah serta lengan. Nyaman dan modis untuk kegiatan lapangan maupun forum santai.',
                'price' => 110000,
                'weight_grams' => 250,
                'stock' => 85,
                'image_path' => 'products/kaos_polo_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Pin Bros Logam Kuningan Lapis Resin ISNU',
                'slug' => 'pin-bros-logam-kuningan-isnu',
                'category' => 'pin',
                'description' => 'Pin bros lencana logam kuningan cor dengan lapisan resin cembung bening mengkilap dan pengait magnet ganda kuat anti-melorot. Menambah kewibawaan saat dikenakan di jas atau batik.',
                'price' => 35000,
                'weight_grams' => 50,
                'stock' => 150,
                'image_path' => 'products/pin_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Buku Khittah & Manifesto Intelektual Sarjana NU',
                'slug' => 'buku-khittah-manifesto-intelektual-sarjana-nu',
                'category' => 'buku',
                'description' => 'Buku referensi resmi yang memuat landasan pemikiran, peta kiprah ilmiah, gagasan kebangsaan, dan khittah perjuangan kader sarjana NU dalam menyongsong Indonesia Emas dan peradaban dunia.',
                'price' => 75000,
                'weight_grams' => 350,
                'stock' => 40,
                'image_path' => 'products/buku_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Peci Songkok Beludru Hitam AC Logo ISNU',
                'slug' => 'peci-songkok-beludru-hitam-ac-isnu',
                'category' => 'aksesoris',
                'description' => 'Songkok hitam nasional berbahan beludru impor halus berkualitas, jahitan rapi dengan ventilasi udara AC di kedua sudut atas, serta ornamen lambang ISNU elegan.',
                'price' => 85000,
                'weight_grams' => 200,
                'stock' => 50,
                'image_path' => 'products/songkok_isnu.jpg',
                'is_active' => true,
                'created_by' => $adminId,
            ],
        ];

        foreach ($products as $pData) {
            $product = Product::updateOrCreate(['slug' => $pData['slug']], $pData);

            // Add sample initial reviews
            if ($adminId && ! $product->reviews()->exists()) {
                ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => $adminId,
                    'rating' => 5,
                    'comment' => 'Kualitas bahan dan pengerjaan merchandise resmi ISNU sangat baik, rapi, dan membanggakan untuk dikenakan di berbagai forum.',
                    'is_approved' => true,
                ]);
            }
        }
    }
}
