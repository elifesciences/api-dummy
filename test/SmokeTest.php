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
        if (strpos('+json', $contentType)) {
            $this->assertTrue(is_array(json_decode($response->getContent(), true)), 'Does not contain a JSON response');
        }
    }

    public function requestProvider() : array
    {
        return [
            [Request::create('/'), 'application/problem+json', 404],
            [Request::create('/annual-reports'), 'application/vnd.elife.annual-report-list+json; version=1'],
            [Request::create('/annual-reports/2012'), 'application/vnd.elife.annual-report+json; version=1'],
            [Request::create('/articles'), 'application/vnd.elife.article-list+json; version=1'],
            [Request::create('/articles/09560'), 'application/vnd.elife.article-vor+json; version=1'],
            [Request::create('/articles/14107'), 'application/vnd.elife.article-poa+json; version=1'],
            [Request::create('/articles/14107/versions'), 'application/vnd.elife.article-history+json; version=1'],
            [Request::create('/articles/14107/versions/1'), 'application/vnd.elife.article-poa+json; version=1'],
            [Request::create('/blog-articles'), 'application/vnd.elife.blog-article-list+json; version=1'],
            [Request::create('/blog-articles/339482'), 'application/vnd.elife.blog-article+json; version=1'],
            [Request::create('/collections'), 'application/vnd.elife.collection-list+json; version=1'],
            [Request::create('/collections/tropical-disease'), 'application/vnd.elife.collection+json; version=1'],
            [Request::create('/events'), 'application/vnd.elife.event-list+json; version=1'],
            [Request::create('/events/1'), 'application/vnd.elife.event+json; version=1'],
            [Request::create('/labs-experiments'), 'application/vnd.elife.labs-experiment-list+json; version=1'],
            [Request::create('/labs-experiments/1'), 'application/vnd.elife.labs-experiment+json; version=1'],
            [Request::create('/medium-articles'), 'application/vnd.elife.medium-article-list+json; version=1'],
            [Request::create('/people'), 'application/vnd.elife.person-list+json; version=1'],
            [Request::create('/people/jpublic'), 'application/vnd.elife.person+json; version=1'],
            [Request::create('/podcast-episodes'), 'application/vnd.elife.podcast-episode-list+json; version=1'],
            [Request::create('/podcast-episodes/1'), 'application/vnd.elife.podcast-episode+json; version=1'],
            [Request::create('/search'), 'application/vnd.elife.search+json; version=1'],
            [Request::create('/search?for=cell'), 'application/vnd.elife.search+json; version=1'],
            [Request::create('/search?subject[]=cell-biology'), 'application/vnd.elife.search+json; version=1'],
            [Request::create('/subjects'), 'application/vnd.elife.subject-list+json; version=1'],
            [Request::create('/subjects/biochemistry'), 'application/vnd.elife.subject+json; version=1'],
            [Request::create('/images/subjects/cell-biology/png'), 'application/problem+json', 404],
            [Request::create('/images/subjects/cell-biology/jpg'), 'image/jpeg'],
            [Request::create('/images/subjects/cell-biology/jpg?width=900'), 'image/jpeg'],
            [Request::create('/images/subjects/cell-biology/jpg?height=450'), 'image/jpeg'],
            [Request::create('/images/subjects/cell-biology/jpg?width=900&height=450'), 'image/jpeg'],
            [Request::create('/images/subjects/cell-biology/jpg?width=5001'), 'application/problem+json', 400],
            [Request::create('/images/subjects/cell-biology/jpg?height=5001'), 'application/problem+json', 400],
        ];
    }
}
