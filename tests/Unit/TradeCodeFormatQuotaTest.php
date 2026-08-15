<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TradeCode;
use PHPUnit\Framework\TestCase;

class TradeCodeFormatQuotaTest extends TestCase
{
    public function test_rounds_to_the_configured_decimal_places_when_rounding_enabled(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 2, 'rounding_enabled' => true]);

        $this->assertSame('12,35', $tradeCode->formatQuota(12.346));
        $this->assertSame('12,34', $tradeCode->formatQuota(12.344));
    }

    public function test_truncates_instead_of_rounding_when_rounding_disabled(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 2, 'rounding_enabled' => false]);

        $this->assertSame('12,34', $tradeCode->formatQuota(12.349));
    }

    public function test_supports_up_to_four_decimal_places(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 4, 'rounding_enabled' => true]);

        $this->assertSame('12,3457', $tradeCode->formatQuota(12.34567));
    }

    public function test_uses_indonesian_thousands_and_decimal_separators(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 2, 'rounding_enabled' => true]);

        $this->assertSame('1.234.567,89', $tradeCode->formatQuota(1234567.89));
    }

    public function test_zero_decimal_places_shows_a_whole_number_with_no_trailing_zeros(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 0, 'rounding_enabled' => true]);

        $this->assertSame('100', $tradeCode->formatQuota(100));
        $this->assertSame('101', $tradeCode->formatQuota(100.6));
    }
}
