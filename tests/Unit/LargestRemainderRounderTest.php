<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KuotaTangkap\LargestRemainderRounder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LargestRemainderRounderTest extends TestCase
{
    public function test_distributes_remainder_to_largest_fractions_so_sum_equals_target(): void
    {
        $rounded = (new LargestRemainderRounder)->round([
            1 => '2213.9981',
            2 => '4242.4753',
            3 => '848.9981',
            4 => '5299.5285',
            5 => '2695.0000',
        ], 15300);

        $this->assertSame([1 => 2214, 2 => 4242, 3 => 849, 4 => 5300, 5 => 2695], $rounded);
        $this->assertSame(15300, array_sum($rounded));
    }

    public function test_exact_integer_value_is_not_floored_down(): void
    {
        $this->assertSame(['a' => 2695], (new LargestRemainderRounder)->round(['a' => '2695.0000'], 2695));
    }

    public function test_equal_fractions_prefer_larger_value(): void
    {
        $this->assertSame([1 => 10, 2 => 21], (new LargestRemainderRounder)->round([1 => '10.5000', 2 => '20.5000'], 31));
    }

    public function test_equal_fraction_and_value_break_ties_by_smaller_key(): void
    {
        $this->assertSame([7 => 10, 3 => 11], (new LargestRemainderRounder)->round([7 => '10.5000', 3 => '10.5000'], 21));
    }

    public function test_rejects_unreachable_target(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LargestRemainderRounder)->round([1 => '10.2000', 2 => '10.2000'], 23);
    }
}
