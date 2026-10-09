<?php

namespace App\Notifications;

use App\Models\TestDrive;

class TestDriveStatusChanged extends StatusChangedNotification
{
    public function __construct(TestDrive $testDrive)
    {
        parent::__construct($testDrive->loadMissing('car.brand'));
    }

    protected function subject(): string
    {
        return 'Test Drive';
    }

    protected function description(): string
    {
        return self::describe($this->record);
    }

    /**
     * Ringkasan data (dipakai juga notifikasi admin).
     */
    public static function describe(TestDrive $testDrive): string
    {
        $car = $testDrive->car;

        return "{$car->brand->name} {$car->name} {$car->year} · "
            .$testDrive->preferred_date->translatedFormat('d M Y')." pukul {$testDrive->timeLabel()} WIB";
    }

    protected function routeName(): string
    {
        return 'account.test-drives.index';
    }

    protected function anchorPrefix(): string
    {
        return 'test-drive';
    }
}
