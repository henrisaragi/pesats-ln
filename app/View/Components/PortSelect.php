<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Port;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PortSelect extends Component
{
    public function __construct(
        public string $id,
        public string $name = 'port_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
        public ?string $countryId = null,
    ) {}

    public function render(): View
    {
        return view('components.port-select', [
            'options' => Port::query()
                ->when($this->countryId, fn ($query) => $query->where('country_id', $this->countryId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
