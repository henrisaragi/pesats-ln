<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DocumentViewerModal extends Component
{
    public function __construct(
        public string $id = 'document-viewer-modal',
    ) {}

    public function render(): View
    {
        return view('components.document-viewer-modal');
    }
}
