<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TradeCode;
use PHPUnit\Framework\TestCase;

class TradeCodeQuotaToWordsTest extends TestCase
{
    public function test_spells_out_the_quota_in_indonesian_with_the_unit_name(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 0, 'rounding_enabled' => true, 'unit_name' => 'Ekor']);

        $this->assertSame('Dua Puluh Ekor', $tradeCode->quotaToWords(20, 'id'));
    }

    public function test_spells_out_the_quota_in_english_with_the_unit_name(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 0, 'rounding_enabled' => true, 'unit_name' => 'Heads']);

        $this->assertSame('Twenty Heads', $tradeCode->quotaToWords(20, 'en'));
    }

    /**
     * Kata-katanya harus mengikuti angka yang SUDAH dibulatkan sesuai decimal_places, bukan nilai
     * float mentah - kalau tidak, tampilan angka ("15,57") dan terbilangnya ("lima belas koma
     * lima enam tujuh") bisa tidak sinkron.
     */
    public function test_words_match_the_rounded_value_not_the_raw_float(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 2, 'rounding_enabled' => true, 'unit_name' => 'Kilogram']);

        $this->assertSame('15,57', $tradeCode->formatQuota(15.567));
        $this->assertSame('Lima Belas Koma Lima Tujuh Kilogram', $tradeCode->quotaToWords(15.567, 'id'));
        $this->assertSame('Fifteen Point Five Seven Kilogram', $tradeCode->quotaToWords(15.567, 'en'));
    }

    public function test_words_match_the_truncated_value_when_rounding_disabled(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 0, 'rounding_enabled' => false, 'unit_name' => 'Pcs']);

        $this->assertSame('12', $tradeCode->formatQuota(12.9));
        $this->assertSame('Dua Belas Pcs', $tradeCode->quotaToWords(12.9, 'id'));
    }
}
