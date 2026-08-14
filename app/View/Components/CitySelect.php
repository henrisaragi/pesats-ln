<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Kota;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class CitySelect extends Component
{
    public ?Kota $selectedOption;

    public function __construct(
        public string $id,
        public string $dependsOn,
        public string $name = 'kota_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {
        $this->selectedOption = $this->selected !== null
            ? Kota::query()->find($this->selected, ['id', 'name'])
            : null;
    }

    public function render(): View
    {
        return view('components.city-select', [
            'options' => $this->selectedOption ? new Collection([$this->selectedOption]) : new Collection,
            'remoteUrl' => route('master.kota.by-provinsi', ['provinsi' => '__PARENT__']),
        ]);
    }
}
