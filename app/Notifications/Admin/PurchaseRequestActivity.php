<?php

namespace App\Notifications\Admin;

use App\Models\PurchaseRequest;
use App\Notifications\PurchaseRequestStatusChanged;

class PurchaseRequestActivity extends AdminActivityNotification
{
    public function __construct(PurchaseRequest $purchaseRequest, string $event = self::CREATED)
    {
        parent::__construct($purchaseRequest->loadMissing('user:id,name', 'car.brand'), $event);
    }

    protected function subject(): string
    {
        return 'Pengajuan Pembelian';
    }

    protected function description(): string
    {
        return PurchaseRequestStatusChanged::describe($this->record)
            .' · Rp '.number_format($this->record->car_price, 0, ',', '.');
    }

    protected function routeName(): string
    {
        return 'admin.purchase-requests.show';
    }
}
