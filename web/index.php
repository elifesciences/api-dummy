<?php

use Crell\ApiProblem\ApiProblem;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app['experiments'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__ . '/../data/experiments');

    $experiments = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $experiments[(int) $json['number']] = $json;
    }

    ksort($experiments);

    return $experiments;
};

$app['podcast-episodes'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__ . '/../data/podcast-episodes');

    $episodes = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $episodes[(int) $json['number']] = $json;
    }

    ksort($episodes);

    return $episodes;
};

$app['subjects'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__ . '/../data/subjects');

    $subjects = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $subjects[$json['id']] = $json;
    }

    ksort($subjects);

    return $subjects;
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

    $experiments = $app['experiments'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($experiments),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $experiments = array_reverse($experiments);
    }

    $experiments = array_slice($experiments, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($experiments) && $page > 1) {
        throw new NotFoundHttpException('No page ' . $page);
    }

    foreach ($experiments as $i => $experiment) {
        unset($experiment['content']);

        $content['items'][] = $experiment;
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
        if (false === isset($app['experiments'][$number])) {
            throw new NotFoundHttpException('Not found');
        };

        $experiment = $app['experiments'][$number];

        $accepts = [
            'application/vnd.elife.labs-experiment+json; version=1'
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($experiment, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*');

$app->get('/podcast-episodes', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.podcast-episode-list+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $episodes = $app['podcast-episodes'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $episodes = array_filter($episodes, function ($episode) use ($subjects) {
            $episodeSubjects = $episode['subjects'] ?? [];

            return count(array_intersect($subjects, $episodeSubjects));
        });
    }

    $content = [
        'total' => count($episodes),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $episodes = array_reverse($episodes);
    }

    $episodes = array_slice($episodes, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($episodes) && $page > 1) {
        throw new NotFoundHttpException('No page ' . $page);
    }

    foreach ($episodes as $i => $episode) {
        $content['items'][] = $episode;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/podcast-episodes/{number}',
    function (Request $request, int $number) use ($app) {
        if (false === isset($app['podcast-episodes'][$number])) {
            throw new NotFoundHttpException('Not found');
        };

        $episode = $app['podcast-episodes'][$number];

        $accepts = [
            'application/vnd.elife.podcast-episode+json; version=1'
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($episode, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*');

$app->get('/subjects', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.subject-list+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $subjects = $app['subjects'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($subjects),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $subjects = array_reverse($subjects);
    }

    $subjects = array_slice($subjects, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($subjects) && $page > 1) {
        throw new NotFoundHttpException('No page ' . $page);
    }

    foreach ($subjects as $i => $subject) {
        unset($subject['content']);

        $content['items'][] = $subject;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/subjects/{id}', function (Request $request, string $id) use ($app) {
    if (false === isset($app['subjects'][$id])) {
        throw new NotFoundHttpException('Not found');
    };

    $subject = $app['subjects'][$id];

    $accepts = [
        'application/vnd.elife.subject+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    return new Response(
        json_encode($subject, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
    );
});

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
