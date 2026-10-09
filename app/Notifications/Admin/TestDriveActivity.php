<?php

namespace App\Notifications\Admin;

use App\Models\TestDrive;
use App\Notifications\TestDriveStatusChanged;

class TestDriveActivity extends AdminActivityNotification
{
    public function __construct(TestDrive $testDrive, string $event = self::CREATED)
    {
        parent::__construct($testDrive->loadMissing('user:id,name', 'car.brand'), $event);
    }

    protected function subject(): string
    {
        return 'Test Drive';
    }

    protected function description(): string
    {
        return TestDriveStatusChanged::describe($this->record);
    }

    protected function routeName(): string
    {
        return 'admin.test-drives.show';
    }
}
