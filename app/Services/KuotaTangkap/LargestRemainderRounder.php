<?php

declare(strict_types=1);

namespace App\Services\KuotaTangkap;

use InvalidArgumentException;

/**
 * Membulatkan sekumpulan nilai desimal non-negatif ke bilangan bulat dengan metode largest
 * remainder (Hamilton) sehingga jumlahnya tepat sama dengan $target. Memakai BCMath supaya
 * nilai yang tepat bulat (mis. 2695.0000) tidak turun ke 2694 karena galat float.
 */
class LargestRemainderRounder
{
    /**
     * Urutan prioritas sisa +1: pecahan terbesar, lalu nilai desimal terbesar, lalu key terkecil.
     *
     * @param  array<int|string, string>  $values  key => desimal non-negatif (string)
     * @return array<int|string, int>
     */
    public function round(array $values, int $target): array
    {
        $floors = [];
        $fractions = [];
        $sumFloor = 0;

        foreach ($values as $key => $value) {
            if (bccomp($value, '0', 10) < 0) {
                throw new InvalidArgumentException('Nilai tidak boleh negatif.');
            }

            $floor = (int) bcadd($value, '0', 0);
            $floors[$key] = $floor;
            $fractions[$key] = bcsub($value, (string) $floor, 10);
            $sumFloor += $floor;
        }

        $remaining = $target - $sumFloor;

        if ($remaining < 0 || $remaining > count($values)) {
            throw new InvalidArgumentException("Target {$target} tidak dapat dicapai dari nilai yang diberikan.");
        }

        $keys = array_keys($values);

        usort($keys, function (int|string $a, int|string $b) use ($fractions, $values): int {
            return bccomp($fractions[$b], $fractions[$a], 10)
                ?: bccomp($values[$b], $values[$a], 10)
                ?: ($a <=> $b);
        });

        foreach (array_slice($keys, 0, $remaining) as $key) {
            $floors[$key]++;
        }

        return $floors;
    }
}
