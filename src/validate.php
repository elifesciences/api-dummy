<?php

use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webmozart\Json\JsonDecoder;

$app = require __DIR__.'/bootstrap.php';

$app['puli.factory'] = function () {
    $factoryClass = PULI_FACTORY_CLASS;

    return new $factoryClass();
};

$app['puli.repository'] = function (Application $app) {
    return $app['puli.factory']->createRepository();
};

$app['message-validator'] = function (Application $app) {
    $jsonDecoder = new JsonDecoder();

    return new FakeHttpsMessageValidator(
        new JsonMessageValidator(new PuliSchemaFinder($app['puli.repository']), $jsonDecoder),
        $jsonDecoder
    );
};

$app['symfony.psr7-factory'] = function (Application $app) {
    return new DiactorosFactory();
};

$app->before(function (Request $request, Application $app) {
    $app['message-validator']->validate($app['symfony.psr7-factory']->createRequest($request));
});

$app->after(function (Request $request, Response $response, Application $app) {
    if ($response instanceof StreamedResponse) {
        return;
    }

    $app['message-validator']->validate($app['symfony.psr7-factory']->createResponse($response));
});

return $app;
