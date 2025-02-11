<?php

use eLife\DummyApi\helpers\TermFilter;

final class TermFilterTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    final public function is_significance_term_found_returns_false_if_there_are_no_results()
    {
        $this->assertFalse(TermFilter::isSignificanceTermFound([], 'important'));
    }

    /**
     * @test
     */
    final public function is_significance_term_found_returns_true_if_result_has_term()
    {
        $this->assertTrue(TermFilter::isSignificanceTermFound([
            'elifeAssessment' => [
                'significance' => [
                    'important',
                ],
            ],
        ], 'important'));
    }

    /**
     * @test
     */
    final public function is_significance_term_found_returns_false_if_there_is_no_significance()
    {
        $this->markTestSkipped();
        $this->assertTrue(TermFilter::isSignificanceTermFound([
            'elifeAssessment' => [
                'strength' => [
                    'solid',
                ],
            ],
        ], 'important'));
    }
};
