<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\TestimonialModerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Moderasi ulasan customer di /admin/ulasan.
 */
class TestimonialModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Testimonial $testimonial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $car = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id, 'name' => 'Livina VL CVT', 'year' => 2025]);
        $purchase = PurchaseRequest::factory()->recycle($this->customer)->recycle($car)->status(PurchaseRequest::STATUS_COMPLETED)->create();
        $this->testimonial = Testimonial::factory()->recycle($this->customer)->create([
            'purchase_request_id' => $purchase->id, 'rating' => 4, 'comment' => 'Pelayanan cepat dan ramah sekali.',
        ]);
    }

    public function test_daftar_dan_filter_status(): void
    {
        Testimonial::factory()->approved()->create(['comment' => 'Ulasan lain yang sudah disetujui.']);

        $this->actingAs($this->admin)->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('Pelayanan cepat dan ramah sekali.')
            ->assertSee('Ulasan lain yang sudah disetujui.')
            ->assertSee('Budi Santoso')
            ->assertSee('Nissan Livina VL CVT 2025');

        $this->actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Pelayanan cepat dan ramah sekali.')
            ->assertDontSee('Ulasan lain yang sudah disetujui.');

        // Nilai filter tidak dikenal diabaikan (semua tampil).
        $this->actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'xx']))
            ->assertOk()
            ->assertSee('Ulasan lain yang sudah disetujui.');
    }

    public function test_setujui_mengirim_notifikasi_dan_tampil_di_beranda(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.testimonials.index'))
            ->patch(route('admin.testimonials.approve', $this->testimonial))
            ->assertRedirect(route('admin.testimonials.index'))
            ->assertSessionHas('success', 'Ulasan disetujui dan kini tampil di beranda.');

        $this->testimonial->refresh();
        $this->assertTrue($this->testimonial->isApproved());
        $this->assertNotNull($this->testimonial->approved_at);

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(TestimonialModerated::class, $notification->type);
        $this->assertSame('Ulasan Disetujui', $notification->data['title']);
        $this->assertSame("pengajuan-{$this->testimonial->purchase_request_id}", $notification->data['anchor']);

        $this->get(route('home'))->assertSee('Pelayanan cepat dan ramah sekali.');

        // Setujui dua kali: tidak ada notifikasi kedua.
        $this->actingAs($this->admin)->patch(route('admin.testimonials.approve', $this->testimonial))->assertSessionHas('status');
        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_tolak_wajib_alasan_dan_customer_melihat_alasan(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.testimonials.reject', $this->testimonial), ['admin_note' => ''])
            ->assertSessionHasErrorsIn("reject{$this->testimonial->id}", 'admin_note');
        $this->assertSame(Testimonial::STATUS_PENDING, $this->testimonial->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.testimonials.reject', $this->testimonial), ['admin_note' => 'Mohon hapus nomor HP dari ulasan.'])
            ->assertSessionHas('success');

        $this->testimonial->refresh();
        $this->assertSame(Testimonial::STATUS_REJECTED, $this->testimonial->status);
        $this->assertSame('Mohon hapus nomor HP dari ulasan.', $this->testimonial->admin_note);

        $notification = $this->customer->notifications()->sole();
        $this->assertSame('Ulasan Ditolak', $notification->data['title']);
        $this->assertSame('Mohon hapus nomor HP dari ulasan.', $notification->data['admin_note']);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Mohon hapus nomor HP dari ulasan.')
            ->assertSee('Perbaiki Ulasan');
    }

    public function test_ulasan_disetujui_bisa_disembunyikan(): void
    {
        $this->testimonial->update(['status' => Testimonial::STATUS_APPROVED, 'approved_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.testimonials.index'))->assertSee('Sembunyikan (Tolak)');

        $this->actingAs($this->admin)
            ->patch(route('admin.testimonials.reject', $this->testimonial), ['admin_note' => 'Ulasan tidak relevan lagi.'])
            ->assertSessionHas('success');

        $this->assertNull($this->testimonial->fresh()->approved_at);
        $this->get(route('home'))->assertDontSee('Pelayanan cepat dan ramah sekali.');
    }

    public function test_hanya_admin(): void
    {
        $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
        $this->actingAs($this->customer)->get(route('admin.testimonials.index'))->assertForbidden();
        $this->actingAs($this->customer)->patch(route('admin.testimonials.approve', $this->testimonial))->assertForbidden();

        $this->assertSame(Testimonial::STATUS_PENDING, $this->testimonial->fresh()->status);
    }

    public function test_menu_sidebar_dan_tanpa_n_plus_1(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.testimonials.index'))
                ->assertOk()
                ->assertSee('href="'.route('admin.testimonials.index').'"', false);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $withOne = $count();
        Testimonial::factory()->count(5)->create();

        $this->assertSame($withOne, $count());
    }
}
