<?php

namespace test\eLife\DummyApi;

use Silex\Application;

trait SilexTestCase
{
    private $app;

    /**
     * @before
     */
    final public function setUpApp()
    {
        $this->app = require __DIR__.'/../src/validate.php';
    }

    final protected function getApp() : Application
    {
        return $this->app;
    }
}
