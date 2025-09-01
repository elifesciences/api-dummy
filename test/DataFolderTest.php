<?php

namespace test\eLife\DummyApi;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Traversable;

final class DataFolderTest extends TestCase
{
    use SilexTestCase;

    private $dataFolder;

    /**
     * @before
     */
    public function setDataFolder()
    {
        $this->dataFolder = getenv('DATA_FOLDER');
        putenv('DATA_FOLDER=test/data1');
    }

    /**
     * @after
     */
    public function resetDataFolder()
    {
        if (is_string($this->dataFolder)) {
            putenv("DATA_FOLDER=$this->dataFolder");
        } else {
            putenv('DATA_FOLDER');
        }
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
                    200,
                    [
                        'application/vnd.elife.blog-article+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                    ],
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
