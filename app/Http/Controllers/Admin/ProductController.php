<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Memeriksa otorisasi: hanya Admin PC ISNU yang dapat mengelola produk toko.
     */
    protected function checkAccess(): void
    {
        if (! auth()->check() || ! auth()->user()->canManageProducts()) {
            abort(403, 'Hanya Admin PC ISNU yang berhak mengelola master barang / produk toko.');
        }
    }

    /**
     * Menampilkan daftar produk merchandise ISNU.
     */
    public function index(Request $request): View
    {
        $this->checkAccess();

        $query = Product::withCount(['orderItems', 'reviews']);

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        $categories = [
            'all' => 'Semua Kategori',
            'seragam' => 'Seragam Resmi',
            'batik' => 'Batik ISNU',
            'kaos' => 'Kaos & Polo',
            'pin' => 'Pin & Lencana',
            'buku' => 'Buku & Literatur',
            'aksesoris' => 'Aksesoris & Cinderamata',
            'lainnya' => 'Lainnya',
        ];

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Menampilkan form tambah produk baru.
     */
    public function create(): View
    {
        $this->checkAccess();

        $categories = [
            'seragam' => 'Seragam Resmi',
            'batik' => 'Batik ISNU',
            'kaos' => 'Kaos & Polo',
            'pin' => 'Pin & Lencana',
            'buku' => 'Buku & Literatur',
            'aksesoris' => 'Aksesoris & Cinderamata',
            'lainnya' => 'Lainnya',
        ];

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Menyimpan produk baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:seragam,batik,kaos,pin,buku,aksesoris,lainnya'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $slug = Str::slug($validated['name']).'-'.strtolower(Str::random(5));

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'weight_grams' => $validated['weight_grams'] ?? 250,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        AuditLog::record(
            auth()->id(),
            'Tambah Produk Merchandise',
            "Menambahkan produk baru '{$product->name}' dengan harga Rp ".number_format($product->price, 0, ',', '.')
        );

        return redirect()->route('admin.products.index')->with('success', 'Produk merchandise berhasil ditambahkan ke katalog.');
    }

    /**
     * Menampilkan form edit produk.
     */
    public function edit(int $id): View
    {
        $this->checkAccess();

        $product = Product::findOrFail($id);

        $categories = [
            'seragam' => 'Seragam Resmi',
            'batik' => 'Batik ISNU',
            'kaos' => 'Kaos & Polo',
            'pin' => 'Pin & Lencana',
            'buku' => 'Buku & Literatur',
            'aksesoris' => 'Aksesoris & Cinderamata',
            'lainnya' => 'Lainnya',
        ];

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Memperbarui data produk.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:seragam,batik,kaos,pin,buku,aksesoris,lainnya'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'weight_grams' => $validated['weight_grams'] ?? 250,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record(
            auth()->id(),
            'Ubah Produk Merchandise',
            "Memperbarui data produk '{$product->name}'."
        );

        return redirect()->route('admin.products.index')->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Menghapus produk (soft delete).
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->checkAccess();

        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        AuditLog::record(
            auth()->id(),
            'Hapus Produk Merchandise',
            "Menghapus produk '{$name}' dari katalog aktif."
        );

        return redirect()->route('admin.products.index')->with('success', "Produk '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle status aktif produk.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $this->checkAccess();

        $product = Product::findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        $statusStr = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status produk '{$product->name}' berhasil {$statusStr}.");
    }
}
