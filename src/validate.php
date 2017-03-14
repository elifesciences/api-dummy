<?php

use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use JsonSchema\Validator;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

$app = require __DIR__.'/bootstrap.php';

$app['message-validator'] = function (Application $app) {
    return new FakeHttpsMessageValidator(
        new JsonMessageValidator(
            new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
            new Validator()
        )
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
