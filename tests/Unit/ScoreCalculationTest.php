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
        $this->assertTrue($result->isCorrectTeamScore);
    }

    public function test_correct_outcome_with_one_team_score_right_is_four_points(): void
    {
        // Predict 2-1, actual 3-1: home win correct, away tally (1) spot on.
        $result = $this->service->score(2, 1, 3, 1);

        $this->assertSame(4, $result->points);
        $this->assertFalse($result->isExact);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectTeamScore);
    }

    public function test_correct_outcome_with_winning_team_score_right_is_four_points(): void
    {
        // Predict 3-2, actual 3-1: home win correct, home tally (3) spot on.
        $result = $this->service->score(3, 2, 3, 1);

        $this->assertSame(4, $result->points);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectTeamScore);
    }

    public function test_correct_outcome_but_both_team_scores_wrong_is_three_points(): void
    {
        // Predict 2-1, actual 3-0: home win correct, neither tally matches.
        $result = $this->service->score(2, 1, 3, 0);

        $this->assertSame(3, $result->points);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertFalse($result->isCorrectTeamScore);
    }

    public function test_draw_without_matching_score_is_three_points(): void
    {
        // Predict 1-1, actual 2-2: draw correct, but no tally matches.
        $result = $this->service->score(1, 1, 2, 2);

        $this->assertSame(3, $result->points);
        $this->assertTrue($result->isCorrectOutcome);
        $this->assertFalse($result->isCorrectTeamScore);
    }

    public function test_matching_a_team_score_without_correct_outcome_scores_nothing(): void
    {
        // Predict 1-1, actual 3-1: away tally matches but the outcome is wrong.
        $result = $this->service->score(1, 1, 3, 1);

        $this->assertSame(0, $result->points);
        $this->assertFalse($result->isCorrectOutcome);
        $this->assertTrue($result->isCorrectTeamScore);
    }

    public function test_wrong_outcome_is_zero_points(): void
    {
        $result = $this->service->score(2, 1, 0, 2);

        $this->assertSame(0, $result->points);
        $this->assertFalse($result->isCorrectOutcome);
    }
}
