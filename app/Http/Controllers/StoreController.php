<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    /**
     * Menampilkan katalog publik produk / merchandise ISNU.
     */
    public function index(Request $request): View
    {
        $query = Product::active()->withCount('reviews')->withAvg('reviews', 'rating');

        // Filter Kategori
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Pencarian Kata Kunci
        if ($request->filled('q')) {
            $keyword = trim((string) $request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Pengurutan (Sorting)
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        // Kategori terdaftar beserta jumlah produk
        $categories = [
            'all' => 'Semua Produk',
            'seragam' => 'Seragam Resmi',
            'batik' => 'Batik ISNU',
            'kaos' => 'Kaos & Polo',
            'pin' => 'Pin & Lencana',
            'buku' => 'Buku & Literatur',
            'aksesoris' => 'Aksesoris & Cinderamata',
        ];

        return view('store.index', compact('products', 'categories'));
    }

    /**
     * Menampilkan detail produk merchandise beserta ulasan bintang dan opsi pembelian.
     */
    public function show(string $slug): View
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['reviews.user'])
            ->firstOrFail();

        // Produk Terkait dalam kategori yang sama
        $relatedProducts = Product::active()
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->limit(4)
            ->get();

        return view('store.show', compact('product', 'relatedProducts'));
    }
}
