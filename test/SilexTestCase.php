<?php

namespace test\eLife\DummyApi;

use Silex\Application;

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
}
