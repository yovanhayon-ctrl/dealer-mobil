<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarRequest;
use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CarController extends Controller
{
    /** Pilihan urutan: nilai query string => [kolom, arah]. */
    public const SORTS = [
        'terbaru' => ['created_at', 'desc'],
        'harga_termurah' => ['price', 'asc'],
        'harga_termahal' => ['price', 'desc'],
        'tahun_terbaru' => ['year', 'desc'],
        'tahun_terlama' => ['year', 'asc'],
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        [$sortColumn, $sortDirection] = self::SORTS[$filters['urut']];

        $cars = Car::query()
            ->with(['brand:id,name', 'category:id,name', 'primaryImage:id,car_id,path'])
            ->withCount(Car::DELETION_BLOCKERS)
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.cars.index', [
            'cars' => $cars,
            'filters' => $filters,
            'hasFilters' => collect($filters)->except('urut')->filter(fn ($value) => $value !== null)->isNotEmpty(),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.cars.create', [
            'car' => new Car(['vehicle_condition' => Car::CONDITION_NEW, 'is_active' => true, 'stock' => 1]),
            ...$this->formOptions(),
        ]);
    }

    public function store(CarRequest $request): RedirectResponse
    {
        $data = $request->carData();
        $brand = Brand::findOrFail($data['brand_id']);

        // Slug dibuat sekali saat tambah dan tidak berubah saat edit (URL publik /mobil/{slug}).
        $car = Car::create([
            ...$data,
            'slug' => Car::uniqueSlug("{$brand->name} {$data['name']} {$data['year']}"),
        ]);

        return redirect()->route('admin.cars.index')
            ->with('success', "Mobil \"{$car->name}\" berhasil ditambahkan.");
    }

    public function edit(Car $car): View
    {
        return view('admin.cars.edit', [
            'car' => $car,
            ...$this->formOptions(),
        ]);
    }

    public function update(CarRequest $request, Car $car): RedirectResponse
    {
        $car->update($request->carData());

        return redirect()->route('admin.cars.index')
            ->with('success', "Mobil \"{$car->name}\" berhasil diperbarui.");
    }

    public function toggleActive(Car $car): RedirectResponse
    {
        $car->update(['is_active' => ! $car->is_active]);

        $status = $car->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back(fallback: route('admin.cars.index'))
            ->with('success', "Mobil \"{$car->name}\" {$status}.");
    }

    public function destroy(Car $car): RedirectResponse
    {
        $blockers = $car->loadCount(Car::DELETION_BLOCKERS)->deletionBlockers();

        if ($blockers !== null) {
            return $this->cannotDelete($car, $blockers);
        }

        try {
            // Baris car_images ikut terhapus lewat cascadeOnDelete.
            $car->delete();
        } catch (QueryException) {
            // Relasi ditambahkan bersamaan (FK restrict): tampilkan pesan ramah, bukan error SQL.
            return $this->cannotDelete($car, 'data terkait');
        }

        // Folder file gambar dihapus hanya setelah data mobil berhasil dihapus.
        Storage::disk(CarImage::DISK)->deleteDirectory(CarImage::directory($car->id));

        return redirect()->route('admin.cars.index')
            ->with('success', "Mobil \"{$car->name}\" berhasil dihapus.");
    }

    private function cannotDelete(Car $car, string $usage): RedirectResponse
    {
        return redirect()->route('admin.cars.index')
            ->with('error', "Mobil \"{$car->name} {$car->year}\" tidak bisa dihapus karena sudah memiliki {$usage}. "
                .'Nonaktifkan mobil ini agar tidak tampil di katalog.');
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, merek: ?int, kategori: ?int, kondisi: ?string, status: ?string, stok: ?string, urut: string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;
        $id = fn (string $key) => ctype_digit((string) $request->query($key)) ? (int) $request->query($key) : null;

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'merek' => $id('merek'),
            'kategori' => $id('kategori'),
            'kondisi' => $pick('kondisi', array_keys(Car::CONDITIONS)),
            'status' => $pick('status', ['aktif', 'nonaktif']),
            'stok' => $pick('stok', ['habis']),
            'urut' => $pick('urut', array_keys(self::SORTS)) ?? 'terbaru',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'], fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['merek'], fn ($q, $brandId) => $q->where('brand_id', $brandId))
            ->when($filters['kategori'], fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($filters['kondisi'], fn ($q, $condition) => $q->where('vehicle_condition', $condition))
            ->when($filters['status'], fn ($q, $status) => $q->where('is_active', $status === 'aktif'))
            ->when($filters['stok'], fn ($q) => $q->where('stock', 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ];
    }
}
