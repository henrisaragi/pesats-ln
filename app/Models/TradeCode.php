<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

class TradeCode extends Model
{
    use HasFactory;

    protected $table = 'trade_codes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'unit',
        'unit_name',
        'decimal_places',
        'rounding_enabled',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'rounding_enabled' => 'boolean',
        ];
    }

    /**
     * Format a quota value using this trade code's configured decimal places and rounding mode.
     */
    public function formatQuota(float|string $quota): string
    {
        return number_format($this->roundedQuota($quota), $this->decimal_places, ',', '.');
    }

    /**
     * "Terbilang" satuan (bukan mata uang) untuk sebuah quota - mis. "Dua Puluh Ekor" - dipakai
     * baris "Total" tabel spesies (satsln.blade.php). Pakai NumberFormatter::SPELLOUT bawaan
     * ekstensi intl (bukan aturan terbilang manual) supaya benar untuk kedua bahasa sekaligus,
     * termasuk nilai desimal ("lima belas koma lima" / "fifteen point five") kalau
     * decimal_places > 0. $locale menerima kode locale ICU biasa, mis. "id" atau "en". Dibulatkan
     * dulu ke decimal_places yang sama seperti formatQuota() - kalau tidak, angka bisa berbeda dari
     * yang tertulis (mis. tampil "15,57" tapi terbilang "lima belas koma lima enam tujuh" dari sisa
     * presisi float mentah).
     */
    public function quotaToWords(float|string $quota, string $locale): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::SPELLOUT);
        $words = $formatter->format($this->roundedQuota($quota));

        if ($words === false) {
            return $this->formatQuota($quota);
        }

        return trim(ucwords($words).' '.$this->unit_name);
    }

    private function roundedQuota(float|string $quota): float
    {
        $decimals = $this->decimal_places;
        $value = (float) $quota;

        return $this->rounding_enabled
            ? round($value, $decimals)
            : floor($value * 10 ** $decimals) / 10 ** $decimals;
    }
}
