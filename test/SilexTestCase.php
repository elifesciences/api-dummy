<?php

namespace test\eLife\DummyApi;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Traversable;

trait SilexTestCase
{
    private static $app;

    final protected function getApp() : Application
    {
        if (empty(self::$app)) {
            self::$app = require __DIR__.'/../src/validate.php';
        }

        return self::$app;
    }

    /**
     * @test
     * @dataProvider requestProvider
     */
    final public function it_returns_valid_responses(Request $request, $contentType, int $statusCode = 200, $warning = [], int $expectedCount = null)
    {
        global $argv;
        $response = $this->getApp()->handle($request);

        if (in_array('--debug', $argv, true) && 500 === $response->getStatusCode()) {
            $json = json_decode($response->getContent(), true);
            if (isset($json['exception'])) {
                $this->fail($json['exception']);
            }
            $this->fail('A 500 response was returned with: '.$response->getContent());
        }

        if (is_int($expectedCount)) {
            $json = json_decode($response->getContent(), true);
            $this->assertSame($expectedCount, (int) $json['total']);
        }

        $this->assertSame($statusCode, $response->getStatusCode(), $response->getContent());
        if (is_array($contentType)) {
            $this->assertContains($response->headers->get('Content-Type'), $contentType);
        } else {
            $this->assertSame($contentType, $response->headers->get('Content-Type'));
        }
        if (strpos('+json', $response->headers->get('Content-Type'))) {
            $this->assertTrue(is_array(json_decode($response->getContent(), true)), 'Does not contain a JSON response');
        }
        if (!empty($warning[$response->headers->get('Content-Type')])) {
            $this->assertSame($warning[$response->headers->get('Content-Type')], $response->headers->get('Warning'));
        } else {
            $this->assertNull($response->headers->get('Warning'));
        }
    }

    private function createRequest(string $uri, string $type = '*/*') : Request
    {
        return Request::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => $type]);
    }

    abstract public function requestProvider() : Traversable;
}
