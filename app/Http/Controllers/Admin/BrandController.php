<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $brands = Brand::query()
            ->withCount('cars')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.brands.index', compact('brands', 'search'));
    }

    public function create(): View
    {
        return view('admin.brands.create', ['brand' => new Brand]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $name = $request->validated('name');

        $brand = Brand::create([
            'name' => $name,
            'slug' => Brand::uniqueSlug($name),
            'logo' => $request->file('logo')?->store(Brand::LOGO_DIRECTORY, Brand::LOGO_DISK),
        ]);

        return redirect()->route('admin.brands.index')
            ->with('success', "Merek \"{$brand->name}\" berhasil ditambahkan.");
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $name = $request->validated('name');
        $oldLogo = $brand->logo;

        $data = [
            'name' => $name,
            'slug' => Brand::uniqueSlug($name, $brand->id),
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store(Brand::LOGO_DIRECTORY, Brand::LOGO_DISK);
        } elseif ($request->boolean('remove_logo')) {
            $data['logo'] = null;
        }

        $brand->update($data);

        // Logo lama dihapus hanya setelah data baru berhasil disimpan.
        if ($oldLogo && array_key_exists('logo', $data) && $oldLogo !== $data['logo']) {
            Storage::disk(Brand::LOGO_DISK)->delete($oldLogo);
        }

        return redirect()->route('admin.brands.index')
            ->with('success', "Merek \"{$brand->name}\" berhasil diperbarui.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $carsCount = $brand->cars()->count();

        if ($carsCount > 0) {
            return $this->cannotDelete($brand, $carsCount);
        }

        try {
            $brand->delete();
        } catch (QueryException) {
            // Mobil ditambahkan bersamaan (FK restrict): tampilkan pesan ramah, bukan error SQL.
            return $this->cannotDelete($brand);
        }

        if ($brand->logo) {
            Storage::disk(Brand::LOGO_DISK)->delete($brand->logo);
        }

        return redirect()->route('admin.brands.index')
            ->with('success', "Merek \"{$brand->name}\" berhasil dihapus.");
    }

    private function cannotDelete(Brand $brand, ?int $carsCount = null): RedirectResponse
    {
        $usage = $carsCount ? "oleh {$carsCount} mobil" : 'oleh data mobil';

        return redirect()->route('admin.brands.index')
            ->with('error', "Merek \"{$brand->name}\" tidak bisa dihapus karena masih dipakai {$usage}.");
    }
}
