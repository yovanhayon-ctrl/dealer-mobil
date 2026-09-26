<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarImageRequest;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CarImageController extends Controller
{
    public function index(Car $car): View
    {
        $car->load('images');

        return view('admin.cars.images.index', [
            'car' => $car,
            'max' => CarImage::MAX_PER_CAR,
        ]);
    }

    public function store(CarImageRequest $request, Car $car): RedirectResponse
    {
        $files = $request->file('images');
        $paths = [];

        try {
            DB::transaction(function () use ($car, $files, &$paths) {
                // Kunci baris mobil agar dua upload bersamaan tidak melewati batas atau membuat dua gambar utama.
                Car::whereKey($car->id)->lockForUpdate()->first();

                $images = CarImage::where('car_id', $car->id);

                if ($images->clone()->count() + count($files) > CarImage::MAX_PER_CAR) {
                    throw ValidationException::withMessages([
                        'images' => 'Maksimal '.CarImage::MAX_PER_CAR.' gambar per mobil.',
                    ]);
                }

                $needsPrimary = ! $images->clone()->where('is_primary', true)->exists();
                $sortOrder = (int) $images->clone()->max('sort_order');

                foreach ($files as $file) {
                    // store() memakai hashName(): nama file acak, bukan nama asli dari pengguna.
                    $path = $file->store(CarImage::directory($car->id), CarImage::DISK);

                    if ($path === false) {
                        throw new RuntimeException('Gagal menyimpan file gambar.');
                    }

                    $paths[] = $path;

                    CarImage::create([
                        'car_id' => $car->id,
                        'path' => $path,
                        'is_primary' => $needsPrimary,
                        'sort_order' => ++$sortOrder,
                    ]);

                    $needsPrimary = false;
                }
            });
        } catch (Throwable $e) {
            // Data gagal disimpan: jangan tinggalkan file yatim di disk.
            Storage::disk(CarImage::DISK)->delete($paths);

            throw $e;
        }

        return $this->backToGallery($car)
            ->with('success', count($files).' gambar berhasil diunggah.');
    }

    public function makePrimary(Car $car, CarImage $image): RedirectResponse
    {
        DB::transaction(function () use ($car, $image) {
            CarImage::where('car_id', $car->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return $this->backToGallery($car)->with('success', 'Gambar utama berhasil diubah.');
    }

    public function move(Car $car, CarImage $image, string $direction): RedirectResponse
    {
        $ids = $this->orderedImageIds($car);
        $position = $ids->search($image->id);
        $target = $direction === 'naik' ? $position - 1 : $position + 1;

        if ($target < 0 || $target >= $ids->count()) {
            $edge = $direction === 'naik' ? 'pertama' : 'terakhir';

            return $this->backToGallery($car)->with('warning', "Gambar sudah berada di urutan {$edge}.");
        }

        $ids = $ids->all();
        [$ids[$position], $ids[$target]] = [$ids[$target], $ids[$position]];

        // Tulis ulang urutan 1..n sekaligus merapikan nilai sort_order yang ganda.
        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                CarImage::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return $this->backToGallery($car)->with('success', 'Urutan gambar berhasil diubah.');
    }

    public function destroy(Car $car, CarImage $image): RedirectResponse
    {
        DB::transaction(function () use ($car, $image) {
            $image->delete();

            $nextId = $this->orderedImageIds($car)->first();

            if ($image->is_primary && $nextId !== null) {
                CarImage::whereKey($nextId)->update(['is_primary' => true]);
            }
        });

        // File dihapus hanya setelah data berhasil dihapus.
        Storage::disk(CarImage::DISK)->delete($image->path);

        $message = $image->is_primary && $car->images()->exists()
            ? 'Gambar berhasil dihapus. Gambar berikutnya dijadikan gambar utama.'
            : 'Gambar berhasil dihapus.';

        return $this->backToGallery($car)->with('success', $message);
    }

    /**
     * @return Collection<int, int>
     */
    private function orderedImageIds(Car $car): Collection
    {
        return CarImage::where('car_id', $car->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');
    }

    private function backToGallery(Car $car): RedirectResponse
    {
        return redirect()->route('admin.cars.images.index', $car);
    }
}
