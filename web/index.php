<?php

use Crell\ApiProblem\ApiProblem;
use eLife\Api\Experiment;
use eLife\Api\ExperimentNotFound;
use eLife\Api\InMemoryExperiments;
use eLife\Api\Serializer\ExperimentNormalizer;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Silex\Application;
use Silex\Provider\SerializerServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->register(new SerializerServiceProvider());

$app['serializer.normalizers'] = $app->extend('serializer.normalizers',
    function () {
        return [new ExperimentNormalizer()];
    }
);

$app['experiments'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__ . '/../data/experiments');

    $experiments = [];
    foreach ($finder as $file) {
        $experiments[] = $app['serializer']->deserialize($file->getContents(), Experiment::class, 'json');
    }

    return new InMemoryExperiments($experiments);
};

$app['negotiator'] = function () {
    return new Negotiator();
};

$app->get('/labs-experiments', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.labs-experiment-list+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $experiments = $app['experiments']->all();

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($experiments),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $experiments = array_reverse($experiments);
    }

    $experiments = array_slice($experiments, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($experiments) && $page > 1) {
        throw new NotFoundHttpException('No page ' . $page);
    }

    foreach ($experiments as $i => $experiment) {
        $content['items'][$i] = json_decode($app['serializer']->serialize($experiment, 'json',
            ['version' => $version, 'partial' => true]), true);
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    if ($request->query->get('foo')) {
        $headers['Warning'] = '299 elifesciences.org "Deprecation: `foo` query string parameter will be removed, use `bar` instead"';
    }

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/labs-experiments/{number}',
    function (Request $request, int $number) use ($app) {
        try {
            $experiment = $app['experiments']->get($number);
        } catch (ExperimentNotFound $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        };

        $accepts = [
            'application/vnd.elife.labs-experiment+json; version=1'
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        $experiment = $app['serializer']->serialize($experiment, 'json', ['version' => $version]);

        return new Response(
            json_encode(json_decode($experiment), JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*');

$app->error(function (Throwable $e) {
    if ($e instanceof HttpExceptionInterface) {
        $status = $e->getStatusCode();
        $message = $e->getMessage();
        $extra = [];
    } else {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Error';
        $extra = [
            'exception' => $e->getMessage(),
            'stacktrace' => $e->getTraceAsString()
        ];
    }

    $problem = new ApiProblem($message);

    foreach ($extra as $key => $value) {
        $problem[$key] = $value;
    }

    return new Response(
        json_encode(json_decode($problem->asJson()), JSON_PRETTY_PRINT),
        $status,
        ['Content-Type' => 'application/problem+json']
    );
});

$app->run();
