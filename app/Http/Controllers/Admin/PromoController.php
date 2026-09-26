<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoRequest;
use App\Models\Car;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PromoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $promos = Promo::query()
            ->with(['car:id,brand_id,name,year', 'car.brand:id,name'])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.promos.index', [
            'promos' => $promos,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter(fn ($value) => $value !== null)->isNotEmpty(),
        ]);
    }

    public function create(): View
    {
        $promo = new Promo([
            'is_active' => true,
            'start_date' => today(),
            'end_date' => today()->addMonth(),
        ]);

        return view('admin.promos.create', [
            'promo' => $promo,
            'carOptions' => $this->carOptions($promo),
        ]);
    }

    public function store(PromoRequest $request): RedirectResponse
    {
        $data = $request->promoData();

        // Slug dibuat sekali saat tambah dan tidak berubah saat edit (URL publik /promo/{slug}).
        $promo = Promo::create([
            ...$data,
            'slug' => Promo::uniqueSlug($data['title']),
            'image' => $request->file('image')?->store(Promo::IMAGE_DIRECTORY, Promo::IMAGE_DISK),
        ]);

        return redirect()->route('admin.promos.index')
            ->with('success', "Promo \"{$promo->title}\" berhasil ditambahkan.");
    }

    public function edit(Promo $promo): View
    {
        return view('admin.promos.edit', [
            'promo' => $promo,
            'carOptions' => $this->carOptions($promo),
        ]);
    }

    public function update(PromoRequest $request, Promo $promo): RedirectResponse
    {
        $data = $request->promoData();
        $oldImage = $promo->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store(Promo::IMAGE_DIRECTORY, Promo::IMAGE_DISK);
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        $promo->update($data);

        // Banner lama dihapus hanya setelah data baru berhasil disimpan.
        if ($oldImage && array_key_exists('image', $data) && $oldImage !== $data['image']) {
            Storage::disk(Promo::IMAGE_DISK)->delete($oldImage);
        }

        return redirect()->route('admin.promos.index')
            ->with('success', "Promo \"{$promo->title}\" berhasil diperbarui.");
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();

        if ($promo->image) {
            Storage::disk(Promo::IMAGE_DISK)->delete($promo->image);
        }

        return redirect()->route('admin.promos.index')
            ->with('success', "Promo \"{$promo->title}\" berhasil dihapus.");
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, status: ?string, jenis: ?string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'status' => $pick('status', array_keys(Promo::STATUS_FILTERS)),
            'jenis' => $pick('jenis', ['mobil', 'umum']),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'], fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($filters['status'], fn ($q, $status) => $q->withStatus(Promo::STATUS_FILTERS[$status][0]))
            ->when($filters['jenis'] === 'mobil', fn ($q) => $q->whereNotNull('car_id'))
            ->when($filters['jenis'] === 'umum', fn ($q) => $q->whereNull('car_id'));
    }

    /**
     * Pilihan mobil "Merek Nama Tahun": hanya mobil aktif, ditambah mobil promo ini (walau kini nonaktif).
     *
     * @return array<int, string>
     */
    private function carOptions(Promo $promo): array
    {
        return Car::query()
            ->with('brand:id,name')
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($promo->car_id, fn ($q, $carId) => $q->orWhere('id', $carId)))
            ->get(['id', 'brand_id', 'name', 'year', 'is_active'])
            ->sortBy(fn (Car $car) => [$car->brand->name, $car->name, $car->year])
            ->mapWithKeys(fn (Car $car) => [
                $car->id => "{$car->brand->name} {$car->name} {$car->year}".($car->is_active ? '' : ' (nonaktif)'),
            ])
            ->all();
    }
}
