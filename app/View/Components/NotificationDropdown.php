<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\NotificationCategory;
use Illuminate\View\Component;
use Illuminate\View\View;

class NotificationDropdown extends Component
{
    public function __construct(
        public string $id = 'notification-dropdown',
    ) {}

    public function render(): View
    {
        return view('components.notification-dropdown', [
            'categories' => NotificationCategory::cases(),
        ]);
    }
}
