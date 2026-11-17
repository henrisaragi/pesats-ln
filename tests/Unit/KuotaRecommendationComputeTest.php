<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KuotaTangkap\KuotaRecommendationService;
use App\Services\KuotaTangkap\LargestRemainderRounder;
use App\Services\KuotaTangkap\RealisasiEksporResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Rumus murni KuotaRecommendationService::compute() terhadap fixture Excel
 * "Formula 2026 kulit reptil" (docs/PRD-pembagian-kuota-tangkap.md §11).
 */
class KuotaRecommendationComputeTest extends TestCase
{
    /**
     * @return array<string, array{e: string, n: string}>
     */
    private function excelInputs(): array
    {
        return [
            'ahmad' => ['e' => '120', 'n' => '0.63'],
            'berkat' => ['e' => '31373', 'n' => '0.54'],
            'dua' => ['e' => '120', 'n' => '0.24'],
            'fendibali' => ['e' => '29600', 'n' => '0.88'],
            'banyuwangi' => ['e' => '0', 'n' => '0.77'],
        ];
    }

    private function service(): KuotaRecommendationService
    {
        return new KuotaRecommendationService(new RealisasiEksporResolver, new LargestRemainderRounder);
    }

    public function test_excel_fixture_with_ten_percent_reserve(): void
    {
        $result = $this->service()->compute($this->excelInputs(), '17000', '0.1', '0.3', '0.7');

        $this->assertSame(15300, $result['kuota_dibagi']);
        $this->assertSame('61213.0000', $result['total_realisasi_ekspor']);
        $this->assertSame('3.06', $result['total_nilai_evaluasi']);
        $this->assertSame(
            ['ahmad' => 2214, 'berkat' => 4242, 'dua' => 849, 'fendibali' => 5300, 'banyuwangi' => 2695],
            array_map(fn (array $row): int => $row['rekomendasi_bulat'], $result['rows']),
        );
        $this->assertSame(
            ['ahmad' => '2213.9981', 'berkat' => '4242.4753', 'dua' => '848.9981', 'fendibali' => '5299.5285', 'banyuwangi' => '2695.0000'],
            array_map(fn (array $row): string => $row['rekomendasi'], $result['rows']),
        );
        $this->assertSame('8.9981', $result['rows']['ahmad']['komponen_ekspor']);
        $this->assertSame('2205.0000', $result['rows']['ahmad']['komponen_evaluasi']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_excel_compatibility_without_reserve(): void
    {
        $result = $this->service()->compute($this->excelInputs(), '17000', '0', '0.3', '0.7');

        $this->assertSame(17000, $result['kuota_dibagi']);
        $this->assertSame(
            ['ahmad' => '2459.9979', 'berkat' => '4713.8614', 'dua' => '943.3312', 'fendibali' => '5888.3650', 'banyuwangi' => '2994.4444'],
            array_map(fn (array $row): string => $row['rekomendasi'], $result['rows']),
        );
        $this->assertSame(17000, array_sum(array_column($result['rows'], 'rekomendasi_bulat')));
    }

    public function test_reserve_is_floored_before_distribution(): void
    {
        $result = $this->service()->compute(['a' => ['e' => '1', 'n' => '1']], '105', '0.1', '0.3', '0.7');

        $this->assertSame(94, $result['kuota_dibagi']);
        $this->assertSame(94, $result['rows']['a']['rekomendasi_bulat']);
    }

    /**
     * @return array<string, array{0: array<string, array{e: string, n: string}>, 1: string, 2: int}>
     */
    public static function zeroTotals(): array
    {
        return [
            'tanpa realisasi ekspor' => [['a' => ['e' => '0', 'n' => '1'], 'b' => ['e' => '0', 'n' => '3']], 'sum_e_zero', 700],
            'tanpa nilai evaluasi' => [['a' => ['e' => '10', 'n' => '0'], 'b' => ['e' => '30', 'n' => '0']], 'sum_n_zero', 300],
        ];
    }

    /**
     * @param  array<string, array{e: string, n: string}>  $inputs
     */
    #[DataProvider('zeroTotals')]
    public function test_zero_total_zeroes_its_component_and_warns_without_redistributing(array $inputs, string $warning, int $expectedTotal): void
    {
        $result = $this->service()->compute($inputs, '1000', '0', '0.3', '0.7');

        $this->assertSame([['code' => $warning]], $result['warnings']);
        $this->assertSame($expectedTotal, array_sum(array_column($result['rows'], 'rekomendasi_bulat')));
    }

    public function test_company_without_export_still_gets_evaluation_share(): void
    {
        $result = $this->service()->compute(['a' => ['e' => '0', 'n' => '1'], 'b' => ['e' => '100', 'n' => '1']], '1000', '0', '0.3', '0.7');

        $this->assertSame('0.0000', $result['rows']['a']['komponen_ekspor']);
        $this->assertSame(350, $result['rows']['a']['rekomendasi_bulat']);
        $this->assertSame(650, $result['rows']['b']['rekomendasi_bulat']);
    }
}
