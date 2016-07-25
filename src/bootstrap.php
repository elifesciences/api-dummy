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

require_once __DIR__.'/../vendor/autoload.php';

$app = new Application();

$app['blog-articles'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/blog-articles');

    $articles = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $articles[$json['id']] = $json;
    }

    uasort($articles, function (array $a, array $b) {
        return DateTimeImmutable::createFromFormat(DATE_ATOM,
            $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
    });

    return $articles;
};

$app['experiments'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/experiments');

    $experiments = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $experiments[(int) $json['number']] = $json;
    }

    ksort($experiments);

    return $experiments;
};

$app['medium-articles'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/medium-articles');

    $articles = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $articles[] = $json;
    }

    usort($articles, function (array $a, array $b) {
        return DateTimeImmutable::createFromFormat(DATE_ATOM,
            $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
    });

    return $articles;
};

$app['people'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/people');

    $people = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $people[$json['id']] = $json;
    }

    uasort($people, function (array $a, array $b) {
        return ($a['name']['surname'].', '.$a['name']['givenNames']) <=> ($b['name']['surname'].', '.$b['name']['givenNames']);
    });

    return $people;
};

$app['podcast-episodes'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/podcast-episodes');

    $episodes = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $episodes[(int) $json['number']] = $json;
    }

    ksort($episodes);

    return $episodes;
};

$app['subjects'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/subjects');

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

$app->get('/blog-articles', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.blog-article-list+json; version=1',
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $articles = $app['blog-articles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $articles = array_filter($articles, function ($article) use ($subjects) {
            $articleSubjects = $article['subjects'] ?? [];

            return count(array_intersect($subjects, $articleSubjects));
        });
    }

    $content = [
        'total' => count($articles),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $articles = array_reverse($articles);
    }

    $articles = array_slice($articles, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($articles) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($articles as $i => $article) {
        $content['items'][] = $article;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/blog-articles/{id}',
    function (Request $request, string $id) use ($app) {
        if (false === isset($app['blog-articles'][$id])) {
            throw new NotFoundHttpException('Not found');
        };

        $article = $app['blog-articles'][$id];

        $accepts = [
            'application/vnd.elife.blog-article+json; version=1',
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($article, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/labs-experiments', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.labs-experiment-list+json; version=1',
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
        throw new NotFoundHttpException('No page '.$page);
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
            'application/vnd.elife.labs-experiment+json; version=1',
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

$app->get('/medium-articles', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.medium-article-list+json; version=1',
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $articles = $app['medium-articles'];

    $content = [
        'items' => array_slice($articles, 0, 10),
    ];

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/people', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.person-list+json; version=1',
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $people = $app['people'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $personType = $request->query->get('type', null);
    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($personType)) {
        $people = array_filter($people, function ($person) use ($personType) {
            return $personType === $person['type'];
        });
    }

    if (false === empty($subjects)) {
        $people = array_filter($people, function ($person) use ($subjects) {
            $personSubjects = $person['research']['expertises'] ?? [];

            return count(array_intersect($subjects, $personSubjects));
        });
    }

    $content = [
        'total' => count($people),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $people = array_reverse($people);
    }

    $people = array_slice($people, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($people) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($people as $i => $person) {
        $content['items'][] = $person;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/people/{id}',
    function (Request $request, string $id) use ($app) {
        if (false === isset($app['people'][$id])) {
            throw new NotFoundHttpException('Not found');
        };

        $person = $app['people'][$id];

        $accepts = [
            'application/vnd.elife.person+json; version=1',
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($person, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/podcast-episodes', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.podcast-episode-list+json; version=1',
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
        throw new NotFoundHttpException('No page '.$page);
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
            'application/vnd.elife.podcast-episode+json; version=1',
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

$app->get('/search', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.search+json; version=1',
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $for = strtolower(trim($request->query->get('for')));

    $sort = $request->query->get('sort', 'relevance');
    $subjects = (array) $request->query->get('subject', []);
    $types = (array) $request->query->get('type', []);

    $results = [];

    foreach ($app['blog-articles'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['content']);
        $result['type'] = 'blog-article';
        $results[] = $result;
    }

    foreach ($app['experiments'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['content']);
        $result['type'] = 'labs-experiment';
        $results[] = $result;
    }

    foreach ($app['podcast-episodes'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        $result['type'] = 'podcast-episode';
        $results[] = $result;
    }

    if ('' !== $for) {
        $results = array_filter($results, function ($result) use ($for) {
            return false !== strpos($result['_search'], $for);
        });
    }

    array_walk($results, function (&$result) {
        unset($result['_search']);
    });

    $allSubjects = array_values($app['subjects']);

    array_walk($allSubjects, function (&$subject) use ($results) {
        $subject = [
            'id' => $subject['id'],
            'name' => $subject['name'],
            'results' => count(array_filter($results, function ($result) use ($subject) {
                return in_array($subject['id'], $result['subjects'] ?? []);
            })),
        ];
    });

    $allTypes = [];
    foreach (['blog-article', 'labs-experiment', 'podcast-episode'] as $contentType) {
        $allTypes[$contentType] = count(array_filter($results, function ($result) use ($contentType) {
            return $contentType === $result['type'];
        }));
    }

    if (false === empty($types)) {
        $results = array_filter($results, function ($result) use ($types) {
            return in_array($result['type'], $types);
        });
    }

    if (false === empty($subjects)) {
        $results = array_filter($results, function ($result) use ($subjects) {
            return count(array_intersect($subjects, $result['subjects'] ?? []));
        });
    }

    $content = [
        'total' => count($results),
        'items' => [],
        'subjects' => $allSubjects,
        'types' => $allTypes,
    ];

    if ('date' === $sort) {
        usort($results, function (array $a, array $b) {
            $aDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
            $bDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $b['published']);

            return $bDate <=> $aDate;
        });
    } else {
    }

    if ('asc' === $request->query->get('order', 'desc')) {
        $results = array_reverse($results);
    }

    $results = array_slice($results, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($results) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    $content['items'] = $results;

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/subjects', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.subject-list+json; version=1',
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
        throw new NotFoundHttpException('No page '.$page);
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
        'application/vnd.elife.subject+json; version=1',
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
            'stacktrace' => $e->getTraceAsString(),
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

return $app;
