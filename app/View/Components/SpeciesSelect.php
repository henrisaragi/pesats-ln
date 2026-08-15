<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Species;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class SpeciesSelect extends Component
{
    public ?Species $selectedOption;

    public function __construct(
        public string $id,
        public string $dependsOn,
        public string $name = 'species_id',
        public ?string $selected = null,
        public ?string $placeholder = null,
        public bool $required = false,
    ) {
        $this->selectedOption = $this->selected !== null
            ? Species::query()->find($this->selected, ['id', 'scientific_name as name'])
            : null;
    }

    public function render(): View
    {
        return view('components.species-select', [
            'options' => $this->selectedOption ? new Collection([$this->selectedOption]) : new Collection,
            'remoteUrl' => route('master.species.by-komoditas', ['komoditas' => '__PARENT__']),
        ]);
    }
}
