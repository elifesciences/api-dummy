<?php

namespace test\eLife\DummyApi;

use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SmokeTest extends PHPUnit_Framework_TestCase
{
    use SilexTestCase;

    /**
     * @test
     * @dataProvider requestProvider
     */
    public function it_returns_valid_responses(Request $request, string $contentType, int $statusCode = 200)
    {
        $response = $this->getApp()->handle($request);

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertTrue(is_array(json_decode($response->getContent(), true)), 'Does not contain a JSON response');
    }

    public function requestProvider() : array
    {
        return [
            [Request::create('/'), 'application/problem+json', 404],
            [Request::create('/blog-articles'), 'application/vnd.elife.blog-article-list+json; version=1'],
            [Request::create('/blog-articles/339482'), 'application/vnd.elife.blog-article+json; version=1'],
            [Request::create('/labs-experiments'), 'application/vnd.elife.labs-experiment-list+json; version=1'],
            [Request::create('/labs-experiments/1'), 'application/vnd.elife.labs-experiment+json; version=1'],
            [Request::create('/medium-articles'), 'application/vnd.elife.medium-article-list+json; version=1'],
            [Request::create('/podcast-episodes'), 'application/vnd.elife.podcast-episode-list+json; version=1'],
            [Request::create('/podcast-episodes/1'), 'application/vnd.elife.podcast-episode+json; version=1'],
            [Request::create('/subjects'), 'application/vnd.elife.subject-list+json; version=1'],
            [Request::create('/subjects/biochemistry'), 'application/vnd.elife.subject+json; version=1'],
        ];
    }
}
