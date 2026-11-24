<?php

declare(strict_types=1);

namespace App\Models\Panduan;

use App\Enums\FiturPanduan;
use App\Models\Concerns\AssignableToFitur;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSuratPernyataan extends Model
{
    use AssignableToFitur;

    protected $table = 'template_surat_pernyataan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'keterangan',
        'file_path',
        'original_filename',
        'fitur',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fitur' => AsEnumCollection::of(FiturPanduan::class),
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Hanya PDF yang bisa dipratinjau di viewer modal; DOC/DOCX cukup diunduh.
     */
    public function isPreviewable(): bool
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }
}
