<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Provinsi;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProvinceSelect extends Component
{
    public function __construct(
        public string $id,
        public string $name = 'provinsi_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {}

    public function render(): View
    {
        return view('components.province-select', [
            'options' => Provinsi::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
