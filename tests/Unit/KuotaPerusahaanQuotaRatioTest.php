<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\KuotaNasional;
use App\Models\KuotaPerusahaan;
use App\Models\TradeCode;
use PHPUnit\Framework\TestCase;

class KuotaPerusahaanQuotaRatioTest extends TestCase
{
    public function test_formats_own_quota_over_national_quota(): void
    {
        $tradeCode = new TradeCode(['decimal_places' => 0, 'rounding_enabled' => true]);

        $kuotaNasional = new KuotaNasional(['quota' => 1000]);
        $kuotaNasional->setRelation('tradeCode', $tradeCode);

        $kuotaPerusahaan = new KuotaPerusahaan(['quota' => 100]);
        $kuotaPerusahaan->setRelation('kuotaNasional', $kuotaNasional);

        $this->assertSame('100/1.000', $kuotaPerusahaan->quotaRatioLabel());
    }

    public function test_returns_null_when_trade_code_is_missing(): void
    {
        $kuotaNasional = new KuotaNasional(['quota' => 1000]);
        $kuotaNasional->setRelation('tradeCode', null);

        $kuotaPerusahaan = new KuotaPerusahaan(['quota' => 100]);
        $kuotaPerusahaan->setRelation('kuotaNasional', $kuotaNasional);

        $this->assertNull($kuotaPerusahaan->quotaRatioLabel());
    }
}
