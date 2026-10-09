<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;

class PurchaseRequestStatusChanged extends StatusChangedNotification
{
    public function __construct(PurchaseRequest $purchaseRequest)
    {
        parent::__construct($purchaseRequest->loadMissing('car.brand'));
    }

    protected function subject(): string
    {
        return 'Pengajuan Pembelian';
    }

    protected function description(): string
    {
        $car = $this->record->car;

        return "{$car->brand->name} {$car->name} {$car->year} · {$this->record->paymentMethodLabel()}";
    }

    protected function routeName(): string
    {
        return 'account.purchase-requests.index';
    }

    protected function anchorPrefix(): string
    {
        return 'pengajuan';
    }
}
