<?php

namespace Tests\Unit;

use App\Services\Scoring\ScoreCalculationService;
use PHPUnit\Framework\TestCase;

class ScoreCalculationTest extends TestCase
{
    private ScoreCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoreCalculationService;
    }

    public function test_exact_score_is_five_points(): void
    {
        $result = $this->service->score(2, 1, 2, 1);

        $this->assertSame(5, $result->points);
        $this->assertTrue($result->isExact);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectGoalDifference);
    }

    public function test_correct_outcome_with_correct_goal_difference_is_four_points(): void
    {
        // Predicted home win by 1, actual home win by 1, but not the exact score.
        $result = $this->service->score(2, 1, 1, 0);

        $this->assertSame(4, $result->points);
        $this->assertFalse($result->isExact);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectGoalDifference);
    }

    public function test_correct_outcome_only_is_three_points(): void
    {
        // Predicted home win by 1, actual home win by 2.
        $result = $this->service->score(2, 1, 3, 1);

        $this->assertSame(3, $result->points);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertFalse($result->isCorrectGoalDifference);
    }

    public function test_correct_draw_with_wrong_score_is_four_points(): void
    {
        // A draw predicted as a draw always has the correct goal difference (0),
        // so 3 (outcome) + 1 (goal difference) = 4.
        $result = $this->service->score(1, 1, 2, 2);

        $this->assertSame(4, $result->points);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectGoalDifference);
    }

    public function test_wrong_outcome_is_zero_points(): void
    {
        $result = $this->service->score(2, 1, 0, 2);

        $this->assertSame(0, $result->points);
        $this->assertFalse($result->isCorrectOutcome);
    }
}
