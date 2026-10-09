<?php

namespace App\Http\Controllers;

use App\Actions\SubmitPurchaseRequest;
use App\Http\Requests\PurchaseRequestRequest;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Support\CreditCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengajuan pembelian oleh customer (/mobil/{slug}/ajukan). Riwayat & pembatalan ada di
 * Account\PurchaseRequestController; pemrosesan oleh admin di Admin\PurchaseRequestController.
 */
class PurchaseRequestController extends Controller
{
    /** Batas POST pengajuan per menit per user (rate limiter "purchase-request"). */
    public const MAX_SUBMISSIONS_PER_MINUTE = 5;

    public function create(Request $request, Car $car, CreditCalculator $credit, SubmitPurchaseRequest $submit): View
    {
        abort_unless($car->is_active, 404);

        $car->load(['brand:id,name', 'category:id,name', 'primaryImage:id,car_id,path', 'activePromos:id,car_id,discount_amount']);
        $user = $request->user();
        $price = $car->finalPrice();

        // Nilai awal dari halaman simulasi (?metode=kredit&dp=…&tenor=…).
        $downPayment = (int) preg_replace('/\D/', '', (string) $request->query('dp'));
        $tenor = (int) $request->query('tenor');

        return view('pages.purchase-requests.create', [
            'car' => $car,
            'isAdmin' => $user->isAdmin(),
            'price' => $price,
            'existing' => $user->isAdmin() ? null : $submit->activeRequest($user, $car),
            'defaultMethod' => $request->query('metode') === 'kredit' ? PurchaseRequest::PAYMENT_CREDIT : PurchaseRequest::PAYMENT_CASH,
            'defaultDownPayment' => $downPayment >= $credit->minDownPayment($price) && $downPayment <= $credit->maxDownPayment($price)
                ? $downPayment
                : $credit->minDownPayment($price),
            'defaultTenor' => in_array($tenor, $credit->tenors(), true) ? $tenor : CreditSimulationController::DEFAULT_TENOR,
            'tenors' => $credit->tenors(),
            'rates' => config('credit.rates'),
        ]);
    }

    public function store(PurchaseRequestRequest $request, Car $car, SubmitPurchaseRequest $submit): RedirectResponse
    {
        abort_unless($car->is_active, 404);

        $purchaseRequest = $submit->handle($request->user(), $car, $request->purchaseData());
        $car->loadMissing('brand:id,name');

        $detail = $purchaseRequest->isCredit()
            ? sprintf('kredit DP %s, %d bulan, cicilan %s/bulan',
                CreditSimulationController::rupiah($purchaseRequest->down_payment),
                $purchaseRequest->tenor_months,
                CreditSimulationController::rupiah($purchaseRequest->monthly_installment))
            : 'cash '.CreditSimulationController::rupiah($purchaseRequest->car_price);

        return redirect()->route('account.purchase-requests.index')->with('success', sprintf(
            'Pengajuan pembelian %s %s %s (%s) berhasil dikirim. Tim kami akan menghubungi Anda.',
            $car->brand->name, $car->name, $car->year, $detail,
        ));
    }
}
