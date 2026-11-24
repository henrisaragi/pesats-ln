<?php

declare(strict_types=1);

namespace App\Models\Panduan;

use App\Enums\FiturPanduan;
use App\Models\Concerns\AssignableToFitur;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sumber video salah satu: video_url (YouTube / link video langsung) ATAU file_path (upload S3).
 */
class VideoPanduan extends Model
{
    use AssignableToFitur;

    protected $table = 'video_panduan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'keterangan',
        'video_url',
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
     * URL embed YouTube (iframe) bila video_url adalah link YouTube, selain itu null.
     */
    public function youtubeEmbedUrl(): ?string
    {
        if ($this->video_url === null) {
            return null;
        }

        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        if (preg_match($pattern, $this->video_url, $matches) !== 1) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
    }

    /**
     * URL yang dimuat iframe viewer modal fullscreen (x-document-viewer-modal): embed YouTube,
     * route stream untuk file upload, atau link video langsung.
     */
    public function viewerUrl(): string
    {
        $embedUrl = $this->youtubeEmbedUrl();

        if ($embedUrl !== null) {
            return $embedUrl.'?autoplay=1&rel=0';
        }

        return $this->file_path !== null
            ? route('video-panduan.stream', $this)
            : (string) $this->video_url;
    }
}
