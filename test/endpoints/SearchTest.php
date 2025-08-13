<?php

namespace test\eLife\DummyApi\endpoints;

use eLife\DummyApi\endpoints\Search;
use PHPUnit\Framework\TestCase;
use Traversable;

final class SearchTest extends TestCase
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
        $resultNotApplicable1 = $this->result('2');
        $expectedNotApplicable1 = [
            'id' => '2',
        ];
        $resultNoSignificance1 = $this->result('3', [], ['baz']);
        $expectedNoSignificance1 = [
            'id' => '3',
            'elifeAssessment' => [
                'significance' => [],
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

        yield 'match not applicable' => [
            [
                $resultNotApplicable1,
            ],
            [
                'not-applicable',
            ],
            'strength',
            [
                $expectedNotApplicable1,
            ],
        ];

        yield 'no match not applicable' => [
            [
                $resultNotApplicable1,
            ],
            [
                'foo',
            ],
            'strength',
            [],
        ];

        yield 'match not assigned' => [
            [
                $resultNoSignificance1,
            ],
            [
                'not-assigned',
            ],
            'significance',
            [
                $expectedNoSignificance1,
            ],
        ];
    }

    private function result(string $id, array $significance = [], array $strength = [])
    {
        $result = [
            'id' => $id,
        ];

        if (count($significance) + count($strength) > 0) {
            $result['elifeAssessment'] = [
                'significance' => $significance,
                'strength' => $strength,
            ];
        }

        return $result;
    }
}
