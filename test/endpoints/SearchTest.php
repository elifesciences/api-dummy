<?php

namespace test\eLife\DummyApi\endpoints;

use eLife\DummyApi\endpoints\Search;
use PHPUnit_Framework_TestCase;
use Traversable;

final class SearchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider filterByTermsProvider
     */
    final public function it_can_be_filtered_by_terms(
        array $results,
        array $terms,
        string $termGroup,
        array $expected
    )
    {
        $this->assertEquals($expected, Search::filterByTerms($results, $terms, $termGroup));
    }

    public function filterByTermsProvider() : Traversable
    {
        $result1 = $this->result('1', ['foo', 'bar'], ['baz']);
        $expected1 = [
            'id' => '1',
            'elifeAssessment' => [
                'significance' => ['foo', 'bar'],
                'strength' => ['baz'],
            ],
        ];
        
        yield 'no term filters' => [
            [
                $result1,
            ],
            [],
            'significance',
            [
                $expected1,
            ],
        ];
        
        yield 'match' => [
            [
                $result1,
            ],
            [
                'baz',
            ],
            'significance',
            [],
        ];
        
        yield 'no match' => [
            [
                $result1,
            ],
            [
                'foo',
            ],
            'significance',
            [
                $expected1,
            ],
        ];
    }

    private function result(string $id, array $significance = [], array $strength = [])
    {
        return [
            'id' => $id,
            'elifeAssessment' => [
                'significance' => $significance,
                'strength' => $strength,
            ],
        ];
    }
}
