<?php

namespace test\eLife\DummyApi;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use Traversable;

final class SmokeSearchTest extends PHPUnit_Framework_TestCase
{
    use SilexTestCase;

    public function requestProvider() : Traversable
    {

        foreach ([2, 1] as $version) {
            $warning = 1 === $version ? [
                'application/vnd.elife.search+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
            ] : [];
            yield ($path = '/search?type[]=reviewed-preprint')." (version: {$version})" => [
                $this->createRequest($path, 'application/vnd.elife.search+json; version='.$version),
                'application/vnd.elife.search+json; version='.$version,
                200,
                $warning,
                1 === $version ? 0 : null,
            ];
            yield ($path = '/search')." (version: {$version})" => [
                $this->createRequest($path, 'application/vnd.elife.search+json; version='.$version),
                'application/vnd.elife.search+json; version='.$version,
                200,
                $warning,
            ];
            yield ($path = '/search?for=cell')." (version: {$version})" => [
                $this->createRequest($path, 'application/vnd.elife.search+json; version='.$version),
                'application/vnd.elife.search+json; version='.$version,
                200,
                $warning,
            ];
            yield ($path = '/search?subject[]=cell-biology')." (version: {$version})" => [
                $this->createRequest($path, 'application/vnd.elife.search+json; version='.$version),
                'application/vnd.elife.search+json; version='.$version,
                200,
                $warning,
            ];
            yield ($path = '/search?start-date=2017-01-01&end-date=2017-01-01')." (version: {$version})" => [
                $this->createRequest($path, 'application/vnd.elife.search+json; version='.$version),
                'application/vnd.elife.search+json; version='.$version,
                200,
                $warning,
            ];
        }
        yield $path = '/search?elifeAssessmentSignificance[]=important' => [
            $this->createRequest($path),
            'application/vnd.elife.search+json; version=2',
            200,
            [],
            7,
        ];
        yield $path = '/search?start-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/search?end-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/search?start-date=2017-01-02&end-date=2017-01-01' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
    }
}
