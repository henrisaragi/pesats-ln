<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NotificationAlertModal extends Component
{
    public function __construct(
        public string $id = 'notification-alert-modal',
    ) {}

    public function render(): View
    {
        return view('components.notification-alert-modal');
    }
}
