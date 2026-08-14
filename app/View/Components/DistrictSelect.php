<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Kecamatan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class DistrictSelect extends Component
{
    public ?Kecamatan $selectedOption;

    public function __construct(
        public string $id,
        public string $dependsOn,
        public string $name = 'kecamatan_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {
        $this->selectedOption = $this->selected !== null
            ? Kecamatan::query()->find($this->selected, ['id', 'name'])
            : null;
    }

    public function render(): View
    {
        return view('components.district-select', [
            'options' => $this->selectedOption ? new Collection([$this->selectedOption]) : new Collection,
            'remoteUrl' => route('master.kecamatan.by-kota', ['kota' => '__PARENT__']),
        ]);
    }
}
