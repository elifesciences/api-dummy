<?php

use Crell\ApiProblem\ApiProblem;
use eLife\Labs\Blocks\Image as ImageBlock;
use eLife\Labs\Blocks\Paragraph;
use eLife\Labs\Experiment;
use eLife\Labs\ExperimentNotFound;
use eLife\Labs\Image;
use eLife\Labs\InMemoryExperiments;
use eLife\Labs\Serializer\ExperimentNormalizer;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Silex\Application;
use Silex\Provider\SerializerServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../vendor/autoload.php';

$experiments = [
    new Experiment(
        1,
        'Experiment 1',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-04-29 17:44:12'),
        new Image('https://placekitten.com/300/300', '', 'image/jpeg', '1:1', 300),
        [
            new Paragraph('Paragraph 1'),
            new ImageBlock(
                new Image('https://placekitten.com/600/300', '', 'image/jpeg', '2:1', 600),
                'Kitteh!'
            ),
            new Paragraph('Paragraph 2'),
        ],
        null,
        false
    ),
];

$app = new Application();

$app->register(new SerializerServiceProvider());

$app['serializer.normalizers'] = $app->share($app->extend('serializer.normalizers',
    function () {
        return [new ExperimentNormalizer()];
    }
));

$app['experiments'] = function () use ($app, $experiments) {
    return new InMemoryExperiments($experiments);
};

$app['negotiator'] = function () {
    return new Negotiator();
};

$app->get('/labs-experiments', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.list+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $experiments = $app['experiments']->all();

    $content = [
        'total' => count($experiments),
        'items' => [],
    ];

    foreach ($experiments as $experiment) {
        $content['items'][] = [
            'type' => 'labs-experiment',
            'id' => $experiment->getNumber(),
            '$ref' => '/labs-experiments/' . $experiment->getNumber(),
        ];
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
            'application/vnd.elife.labs-experiment+json; version=2',
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

$app->error(function (Exception $e) {
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
