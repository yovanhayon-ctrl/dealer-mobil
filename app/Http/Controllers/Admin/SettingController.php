<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Notifications\Admin\BankAccountChanged;
use App\Support\CarImageProcessor;
use Illuminate\Http\RedirectResponse;
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

    /** Nama kunci pengaturan di Riwayat Aktivitas. */
    private const LOG_LABELS = [
        'name' => 'nama dealer',
        'tagline' => 'tagline',
        'address' => 'alamat',
        'phone' => 'telepon',
        'whatsapp' => 'WhatsApp',
        'email' => 'email',
        'hours' => 'jam operasional',
        'maps_embed_url' => 'peta',
        'about_text' => 'teks Siapa Kami',
        'hero.kicker' => 'teks hero',
        'hero.title' => 'teks hero',
        'hero.lead' => 'teks hero',
        'hero.background' => 'latar hero',
        'hero.image' => 'gambar banner',
    ];

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

        $changed = Setting::store($values, $request->user());

        if ($oldImage && array_key_exists('hero.image', $values) && $oldImage !== $values['hero.image']) {
            Storage::disk('public')->delete($oldImage);
        }

        $newBank = config('dealer.bank');

        $labels = collect($changed)->reject(fn ($key) => in_array($key, Setting::BANK_KEYS, true))
            ->map(fn ($key) => self::LOG_LABELS[$key] ?? $key)->unique()->values();

        if ($labels->isNotEmpty()) {
            ActivityLog::record(ActivityLog::CATEGORY_ACCOUNT, 'settings.updated',
                'Mengubah Pengaturan: '.$labels->implode(', '), properties: ['keys' => array_values(array_diff($changed, Setting::BANK_KEYS))]);
        }

        if (BankAccountChanged::describe($oldBank) !== BankAccountChanged::describe($newBank)) {
            ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'settings.bank_changed',
                'Mengubah rekening pembayaran: '.BankAccountChanged::describe($oldBank).' → '.BankAccountChanged::describe($newBank),
                properties: ['old' => $oldBank, 'new' => $newBank]);
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
