<?php

namespace Tests\Feature;

use App\Models\MechanicRating;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Rating mekanik dari customer: satu kali per booking selesai, hanya internal (owner, admin, mekanik itu sendiri).
 */
class MechanicRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $mechanic;

    private ServiceBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $this->mechanic = User::factory()->mechanic()->create(['name' => 'Joko Prasetyo']);
        $this->booking = ServiceBooking::factory()->recycle($this->customer)->recycle(Service::factory()->create(['name' => 'Servis Berkala']))->create([
            'status' => ServiceBooking::STATUS_COMPLETED,
            'mechanic_id' => $this->mechanic->id,
            'plate_number' => 'B 1234 ABC',
        ]);
    }

    private function rate(array $payload = [], ?User $user = null, ?ServiceBooking $booking = null)
    {
        $booking ??= $this->booking;

        return $this->actingAs($user ?? $this->customer)
            ->from(route('account.service-bookings.index'))
            ->post(route('account.mechanic-ratings.store', $booking), ['rating' => 5, 'comment' => 'Penjelasannya jelas, mobil kembali bersih.', ...$payload]);
    }

    public function test_customer_memberi_rating_setelah_servis_selesai(): void
    {
        $this->actingAs($this->customer)->get(route('account.service-bookings.index'))
            ->assertSee('Bagaimana pekerjaan Joko Prasetyo?')
            ->assertSee(route('account.mechanic-ratings.store', $this->booking), false);

        $this->rate()
            ->assertRedirect(route('account.service-bookings.index')."#servis-{$this->booking->id}")
            ->assertSessionHas('success');

        $rating = MechanicRating::query()->sole();
        $this->assertSame([5, $this->mechanic->id, $this->customer->id], [$rating->rating, $rating->mechanic_id, $rating->user_id]);

        $this->get(route('account.service-bookings.index'))
            ->assertSee('Rating Anda untuk Joko Prasetyo:')
            ->assertSee('Penjelasannya jelas, mobil kembali bersih.')
            ->assertDontSee('Bagaimana pekerjaan');
    }

    public function test_rating_hanya_sekali_per_booking(): void
    {
        $this->rate();
        $this->rate(['rating' => 1])->assertSessionHas('status', 'Anda sudah memberi rating untuk servis ini.');

        $this->assertSame(5, MechanicRating::query()->sole()->rating);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidRatings(): array
    {
        return [
            'tanpa bintang' => [['rating' => ''], 'rating'],
            'nol' => [['rating' => 0], 'rating'],
            'enam' => [['rating' => 6], 'rating'],
            'komentar terlalu panjang' => [['comment' => str_repeat('a', 501)], 'comment'],
        ];
    }

    #[DataProvider('invalidRatings')]
    public function test_validasi_rating(array $payload, string $field): void
    {
        $this->rate($payload)->assertSessionHasErrorsIn("rating{$this->booking->id}", $field);
        $this->assertSame(0, MechanicRating::count());
    }

    public function test_tidak_bisa_rating_sebelum_selesai_tanpa_mekanik_atau_milik_orang_lain(): void
    {
        $this->booking->update(['status' => ServiceBooking::STATUS_IN_PROGRESS]);
        $this->rate()->assertSessionHas('status', 'Rating mekanik bisa diberikan setelah servis selesai.');

        $this->booking->update(['status' => ServiceBooking::STATUS_COMPLETED, 'mechanic_id' => null]);
        $this->rate()->assertSessionHas('status');

        $this->booking->update(['mechanic_id' => $this->mechanic->id]);
        $this->rate(user: User::factory()->create())->assertNotFound();

        $this->assertSame(0, MechanicRating::count());
    }

    public function test_mekanik_melihat_rating_sendiri_dengan_nama_disingkat(): void
    {
        MechanicRating::factory()->for($this->booking, 'booking')->create([
            'mechanic_id' => $this->mechanic->id, 'user_id' => $this->customer->id, 'rating' => 4, 'comment' => 'Cepat dan rapi.',
        ]);
        $other = User::factory()->mechanic()->create();
        MechanicRating::factory()->create(['mechanic_id' => $other->id, 'rating' => 1, 'comment' => 'Komentar mekanik lain']);

        $this->actingAs($this->mechanic)->get(route('mechanic.ratings.index'))
            ->assertOk()
            ->assertSee('4,0')
            ->assertSee('Budi S.')
            ->assertDontSee('Budi Santoso')
            ->assertSee('Cepat dan rapi.')
            ->assertSee('B 1234 ABC')
            ->assertDontSee('Komentar mekanik lain');

        $this->actingAs($this->customer)->get(route('mechanic.ratings.index'))->assertForbidden();
    }

    public function test_owner_dan_admin_melihat_kinerja_mekanik(): void
    {
        MechanicRating::factory()->for($this->booking, 'booking')->create(['mechanic_id' => $this->mechanic->id, 'user_id' => $this->customer->id, 'rating' => 5]);
        MechanicRating::factory()->create(['mechanic_id' => $this->mechanic->id, 'rating' => 4]);

        $this->actingAs(User::factory()->owner()->create())->get(route('admin.staff.index'))
            ->assertSee('4,5')
            ->assertSee('(2 rating)')
            ->assertSee('2 servis selesai');

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.service-bookings.show', $this->booking))
            ->assertSee('Rating customer untuk mekanik:')
            ->assertSee('Rating 5 dari 5');
    }

    public function test_rating_tidak_tampil_di_halaman_publik(): void
    {
        MechanicRating::factory()->for($this->booking, 'booking')->create([
            'mechanic_id' => $this->mechanic->id, 'user_id' => $this->customer->id, 'comment' => 'Komentar internal bengkel',
        ]);

        foreach (['home', 'services.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('Komentar internal bengkel');
        }
    }
}
