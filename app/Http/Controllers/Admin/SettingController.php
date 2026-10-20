<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Notifications\Admin\BankAccountChanged;
use App\Support\CarImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Admin > Pengaturan (Owner & Super Admin): profil dealer, teks "Siapa Kami", rekening pembayaran, dan hero beranda.
 * Nilai disimpan di tabel settings dan menggantikan nilai .env (lihat App\Models\Setting).
 */
class SettingController extends Controller
{
    /** Lebar maksimal gambar banner hero setelah diproses (WebP). */
    public const HERO_MAX_WIDTH = 1920;

    public function edit(): View
    {
        $heroImage = config('dealer.hero.image');

        return view('admin.settings.edit', [
            'dealer' => config('dealer'),
            'heroImageUrl' => filled($heroImage) ? Storage::disk('public')->url($heroImage) : null,
            'hasStoredHeroImage' => filled(Setting::values()['hero.image'] ?? null),
            // Perubahan rekening terakhir (baris pengaturan rekening yang paling baru diubah).
            'bankEdit' => Setting::query()->with('editor:id,name')->whereIn('key', Setting::BANK_KEYS)->latest('updated_at')->first(),
        ]);
    }

    public function update(SettingRequest $request, CarImageProcessor $processor): RedirectResponse
    {
        $values = $request->settings();
        $oldImage = Setting::values()['hero.image'] ?? null;
        $oldBank = config('dealer.bank');

        if ($request->hasFile('hero_image')) {
            $values['hero.image'] = $this->storeHeroImage($request, $processor);
        } elseif ($request->boolean('remove_hero_image')) {
            $values['hero.image'] = null;
        }

        Setting::store($values, $request->user());

        if ($oldImage && array_key_exists('hero.image', $values) && $oldImage !== $values['hero.image']) {
            Storage::disk('public')->delete($oldImage);
        }

        $newBank = config('dealer.bank');

        if (BankAccountChanged::describe($oldBank) !== BankAccountChanged::describe($newBank)) {
            Log::info('Rekening pembayaran dealer diubah.', [
                'user_id' => $request->user()->id, 'old' => BankAccountChanged::describe($oldBank), 'new' => BankAccountChanged::describe($newBank),
            ]);
            BankAccountChanged::notifyOwners(new BankAccountChanged($request->user(), $oldBank, $newBank));
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan disimpan.');
    }

    private function storeHeroImage(SettingRequest $request, CarImageProcessor $processor): string
    {
        try {
            return $processor->storeSingle($request->file('hero_image')->getRealPath(), Setting::HERO_DIRECTORY, self::HERO_MAX_WIDTH);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'hero_image' => 'Gambar banner tidak bisa diproses. Coba simpan ulang sebagai JPG lalu unggah lagi.',
            ]);
        }
    }
}
