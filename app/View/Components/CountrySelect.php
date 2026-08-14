<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Country;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CountrySelect extends Component
{
    public function __construct(
        public string $id,
        public string $name = 'country_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {}

    public function render(): View
    {
        return view('components.country-select', [
            'options' => Country::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
