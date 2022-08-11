<?php

namespace test\eLife\DummyApi;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use Traversable;

final class DataFolderTest extends PHPUnit_Framework_TestCase
{
    use SilexTestCase;

    /**
     * @before
     */
    public function setDataFolder()
    {
        putenv('DATA_FOLDER=test/data1');
    }

    public function requestProvider() : Traversable
    {
        yield $path = '/' => [
            $this->createRequest($path),
            'application/problem+json',
            404,
        ];

        yield $path = '/annual-reports' => [
            $this->createRequest($path),
            'application/vnd.elife.annual-report-list+json; version=2',
            200,
            [],
            0,
        ];

        yield $path = '/articles' => [
            $this->createRequest($path),
            'application/vnd.elife.article-list+json; version=1',
            200,
            [],
            0,
        ];

        yield $path = '/blog-articles' => [
            $this->createRequest($path),
            'application/vnd.elife.blog-article-list+json; version=1',
            200,
            [],
            2,
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../test/data1/blog-articles') as $file) {
            $path = '/blog-articles/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.blog-article+json; version=2',
            ];
            if (!in_array($file->getBasename('.json'), ['359325'])) {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.blog-article+json; version=1'),
                    'application/vnd.elife.blog-article+json; version=1',
                ];
            } else {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.blog-article+json; version=1'),
                    'application/problem+json',
                    406,
                ];
            }
        }
    }
}
