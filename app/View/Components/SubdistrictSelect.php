<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Kelurahan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class SubdistrictSelect extends Component
{
    public ?Kelurahan $selectedOption;

    public function __construct(
        public string $id,
        public string $dependsOn,
        public string $name = 'kelurahan_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {
        $this->selectedOption = $this->selected !== null
            ? Kelurahan::query()->find($this->selected, ['id', 'name'])
            : null;
    }

    public function render(): View
    {
        return view('components.subdistrict-select', [
            'options' => $this->selectedOption ? new Collection([$this->selectedOption]) : new Collection,
            'remoteUrl' => route('master.kelurahan.by-kecamatan', ['kecamatan' => '__PARENT__']),
        ]);
    }
}
