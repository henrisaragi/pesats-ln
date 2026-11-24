<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\FiturPanduan;
use Illuminate\Database\Eloquent\Builder;

/**
 * Untuk model dengan kolom jsonb "fitur" (array FiturPanduan) dan "is_active".
 */
trait AssignableToFitur
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeUntukFitur(Builder $query, FiturPanduan $fitur): void
    {
        $query->where('is_active', true)->whereJsonContains('fitur', $fitur->value);
    }

    public function isAssignedTo(FiturPanduan $fitur): bool
    {
        return $this->fitur->contains($fitur);
    }
}
