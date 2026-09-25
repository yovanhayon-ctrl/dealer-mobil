<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $categories = Category::query()
            ->withCount('cars')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('admin.categories.create', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $name = $request->validated('name');

        $category = Category::create([
            'name' => $name,
            'slug' => Category::uniqueSlug($name),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil ditambahkan.");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $name = $request->validated('name');

        $category->update([
            'name' => $name,
            'slug' => Category::uniqueSlug($name, $category->id),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil diperbarui.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $carsCount = $category->cars()->count();

        if ($carsCount > 0) {
            return $this->cannotDelete($category, $carsCount);
        }

        try {
            $category->delete();
        } catch (QueryException) {
            // Mobil ditambahkan bersamaan (FK restrict): tampilkan pesan ramah, bukan error SQL.
            return $this->cannotDelete($category);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil dihapus.");
    }

    private function cannotDelete(Category $category, ?int $carsCount = null): RedirectResponse
    {
        $usage = $carsCount ? "oleh {$carsCount} mobil" : 'oleh data mobil';

        return redirect()->route('admin.categories.index')
            ->with('error', "Kategori \"{$category->name}\" tidak bisa dihapus karena masih dipakai {$usage}.");
    }
}
