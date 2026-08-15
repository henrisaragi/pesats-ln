<?php

declare(strict_types=1);

namespace App\Services\Entitas;

use App\Models\Entitas\Entitas;
use App\Models\Entitas\PenangkaranDetail;
use App\Models\Entitas\PeroranganDetail;
use App\Models\Entitas\PerusahaanDetail;
use App\Models\Entitas\UptKsdaDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class EntityProfileService
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const MODELS = [
        'perusahaan' => PerusahaanDetail::class,
        'penangkaran' => PenangkaranDetail::class,
        'perorangan' => PeroranganDetail::class,
        'upt_ksda' => UptKsdaDetail::class,
    ];

    /**
     * Field metadata (label + unique validation rules) per entity_type, used to build
     * both the create/edit form and the read-only profile view.
     *
     * @var array<string, array<string, array{label: string, rules: list<string>}>>
     */
    private const FIELDS = [
        'perusahaan' => [
            'npwp' => ['label' => 'NPWP', 'rules' => ['required', 'string', 'max:30']],
            'nib' => ['label' => 'Nomor Induk Berusaha (NIB)', 'rules' => ['required', 'string', 'max:30']],
        ],
        'penangkaran' => [
            'nomor_izin_penangkaran' => ['label' => 'Nomor Izin Penangkaran', 'rules' => ['required', 'string', 'max:50']],
        ],
        'perorangan' => [
            'nik' => ['label' => 'NIK', 'rules' => ['required', 'digits:16']],
        ],
        'upt_ksda' => [
            'kode_upt' => ['label' => 'Kode UPT', 'rules' => ['required', 'string', 'max:30']],
        ],
    ];

    /**
     * @return array<string, array{label: string, rules: list<string>}>
     */
    public function fields(string $entityType): array
    {
        return self::FIELDS[$entityType] ?? [];
    }

    public function find(Entitas $entity): ?Model
    {
        $modelClass = self::MODELS[$entity->entity_type] ?? null;

        if ($modelClass === null) {
            return null;
        }

        return $modelClass::query()->where('entity_id', $entity->id)->first();
    }

    /**
     * Validation rules for the profile form, with unique checks scoped to the
     * relevant table and ignoring the entity's own existing record (if any).
     *
     * @return array<string, mixed>
     */
    public function rules(Entitas $entity): array
    {
        $modelClass = self::MODELS[$entity->entity_type] ?? null;

        if ($modelClass === null) {
            return [];
        }

        $existing = $this->find($entity);
        $table = (new $modelClass)->getTable();

        $rules = [];

        foreach ($this->fields($entity->entity_type) as $field => $meta) {
            $rules[$field] = [
                ...$meta['rules'],
                Rule::unique($table, $field)->ignore($existing?->id),
            ];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Entitas $entity, array $data): Model
    {
        $modelClass = self::MODELS[$entity->entity_type];

        return $modelClass::query()->updateOrCreate(
            ['entity_id' => $entity->id],
            $data,
        );
    }

    public function delete(Entitas $entity): void
    {
        $this->find($entity)?->delete();
    }
}
