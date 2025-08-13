<?php

namespace test\eLife\DummyApi;

use PHPUnit_Framework_TestCase;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

final class PingTest extends PHPUnit_Framework_TestCase
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
     */
    final public function it_can_be_pinged()
    {
        $response = $this->getApp()->handle(Request::create('/ping'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('pong', $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
    }
}
