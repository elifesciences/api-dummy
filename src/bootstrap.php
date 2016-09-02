<?php

use Crell\ApiProblem\ApiProblem;
use eLife\DummyApi\UnsupportedVersion;
use eLife\DummyApi\VersionedNegotiator;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Negotiation\Accept;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Application();

$app['annual-reports'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/annual-reports');

    $reports = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $reports[$json['year']] = $json;
    }

    ksort($reports);

    return $reports;
};

$app['articles'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/articles');

    $articles = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $articles[$json['versions'][0]['id']] = $json;
    }

    uasort($articles, function (array $article1, array $article2) {
        $article1Dates = [
            'poa' => null,
            'vor' => null,
        ];

        $article2Dates = [
            'poa' => null,
            'vor' => null,
        ];

        foreach ($article1['versions'] as $version) {
            if (null === $article1Dates[$version['status']]) {
                $article1Dates[$version['status']] = DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $version['published']);
            }
        }

        foreach ($article2['versions'] as $version) {
            if (null === $article2Dates[$version['status']]) {
                $article2Dates[$version['status']] = DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $version['published']);
            }
        }

        $article1Date = $article1Dates['vor'] ?? $article1Dates['poa'];
        $article2Date = $article2Dates['vor'] ?? $article2Dates['poa'];

        return $article1Date <=> $article2Date;
    });

    return $articles;
};

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

$app['collections'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/collections');

    $collections = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $collections[$json['id']] = $json;
    }

    uasort($collections, function (array $a, array $b) {
        return DateTimeImmutable::createFromFormat(DATE_ATOM,
            $b['updated']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['updated']);
    });

    return $collections;
};

$app['events'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/events');

    $events = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $events[$json['id']] = $json;
    }

    uasort($events, function (array $a, array $b) {
        return DateTimeImmutable::createFromFormat(DATE_ATOM,
            $b['starts']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['starts']);
    });

    return $events;
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

$app['interviews'] = function () use ($app) {
    $finder = (new Finder())->files()->name('*.json')->in(__DIR__.'/../data/interviews');

    $interviews = [];
    foreach ($finder as $file) {
        $json = json_decode($file->getContents(), true);
        $interviews[$json['id']] = $json;
    }

    uasort($interviews, function (array $a, array $b) {
        return DateTimeImmutable::createFromFormat(DATE_ATOM,
            $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
    });

    return $interviews;
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
        return $a['name']['index'] <=> $b['name']['index'];
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

$app['filesystem'] = function () {
    return new Filesystem(new Local(__DIR__.'/../cache'));
};

$app['imagine'] = function () {
    return new Imagine();
};

$app['negotiator'] = function () {
    return new VersionedNegotiator();
};

$app->get('/annual-reports', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.annual-report-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $reports = $app['annual-reports'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($reports),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $reports = array_reverse($reports);
    }

    $reports = array_slice($reports, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($reports) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($reports as $i => $report) {
        unset($report['content']);

        $content['items'][] = $report;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/annual-reports/{year}',
    function (Request $request, int $year) use ($app) {
        if (false === isset($app['annual-reports'][$year])) {
            throw new NotFoundHttpException('Not found');
        }

        $report = $app['annual-reports'][$year];

        $accepts = [
            'application/vnd.elife.annual-report+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($report, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*')
;

$app->get('/articles', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.article-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $articles = $app['articles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($articles),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $articles = array_reverse($articles);
    }

    if ($request->query->has('subject')) {
        $articles = array_filter($articles, function (array $article) use ($request) : bool {
            $latestVersion = $article['versions'][count($article['versions']) - 1];

            return count(array_intersect((array) $request->query->get('subject'), $latestVersion['subjects']));
        });
    }

    $articles = array_slice($articles, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($articles) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($articles as $i => $article) {
        $latestVersion = $article['versions'][count($article['versions']) - 1];

        unset($latestVersion['issue']);
        unset($latestVersion['copyright']);
        unset($latestVersion['researchOrganisms']);
        unset($latestVersion['keywords']);
        unset($latestVersion['relatedArticles']);
        unset($latestVersion['abstract']);
        unset($latestVersion['digest']);
        unset($latestVersion['body']);
        unset($latestVersion['decisionLetter']);
        unset($latestVersion['authorResponse']);

        $content['items'][] = $latestVersion;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/articles/{number}',
    function (Request $request, string $number) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $latestVersion = count($app['articles'][$number]['versions']);

        $subRequest = Request::create('/articles/'.$number.'/versions/'.$latestVersion, 'GET', array(),
            $request->cookies->all(), array(), $request->server->all());

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }
);

$app->get('/articles/{number}/versions',
    function (Request $request, string $number) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $article = $app['articles'][$number];

        $accepts = [
            'application/vnd.elife.article-history+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        if (!empty($article['received'])) {
            $content = [
                'received' => $article['received'],
                'accepted' => $article['accepted'],
            ];
        }

        $content['poa'] = 0;
        $content['vor'] = 0;

        foreach ($article['versions'] as $articleVersion) {
            ++$content[$articleVersion['status']];
        }

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    }
);

$app->get('/articles/{number}/versions/{version}',
    function (Request $request, string $number, int $version) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $article = $app['articles'][$number];

        if (false === isset($article['versions'][$version - 1])) {
            throw new NotFoundHttpException('Version not found');
        }

        $articleVersion = $article['versions'][$version - 1];

        if ('vor' === $articleVersion['status']) {
            $accepts = [
                'application/vnd.elife.article-vor+json; version=1',
            ];
        } else {
            $accepts = [
                'application/vnd.elife.article-poa+json; version=1',
            ];
        }

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($articleVersion, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    }
);

$app->get('/blog-articles', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.blog-article-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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
        }

        $article = $app['blog-articles'][$id];

        $accepts = [
            'application/vnd.elife.blog-article+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($article, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/collections', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.collection-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $collections = $app['collections'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);
    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $collections = array_filter($collections, function ($collection) use ($subjects) {
            $collectionSubjects = $collection['subjects'] ?? [];

            return count(array_intersect($subjects, $collectionSubjects));
        });
    }

    $content = [
        'total' => count($collections),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $collections = array_reverse($collections);
    }

    $collections = array_slice($collections, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($collections) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($collections as $i => $collection) {
        unset($collection['curators']);
        unset($collection['content']);
        unset($collection['relatedContent']);
        unset($collection['podcastEpisodes']);

        $content['items'][] = $collection;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/collections/{id}',
    function (Request $request, string $id) use ($app) {
        if (false === isset($app['collections'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $collection = $app['collections'][$id];

        $accepts = [
            'application/vnd.elife.collection+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($collection, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/events', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.event-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $events = $app['events'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $eventType = $request->query->get('type', 'all');

    $now = new DateTimeImmutable();

    if ('open' === $eventType) {
        $events = array_filter($events, function ($event) use ($now) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM, $event['ends']) > $now;
        });
    } elseif ('closed' === $eventType) {
        $events = array_filter($events, function ($event) use ($now) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM, $event['ends']) <= $now;
        });
    }

    $content = [
        'total' => count($events),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $events = array_reverse($events);
    }

    $events = array_slice($events, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($events) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($events as $i => $event) {
        unset($event['content']);
        unset($event['venue']);

        $content['items'][] = $event;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/events/{id}',
    function (Request $request, string $id) use ($app) {
        if (false === isset($app['events'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $events = $app['events'][$id];

        $accepts = [
            'application/vnd.elife.event+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($events, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/interviews', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.interview-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $interviews = $app['interviews'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($interviews),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $interviews = array_reverse($interviews);
    }

    $interviews = array_slice($interviews, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($interviews) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($interviews as $i => $interview) {
        unset($interview['interviewee']['cv']);
        unset($interview['content']);

        $content['items'][] = $interview;
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/interviews/{id}',
    function (Request $request, string $id) use ($app) {
        if (false === isset($app['interviews'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $interview = $app['interviews'][$id];

        $accepts = [
            'application/vnd.elife.interview+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($interview, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    });

$app->get('/labs-experiments', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.labs-experiment-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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
        }

        $experiment = $app['experiments'][$number];

        $accepts = [
            'application/vnd.elife.labs-experiment+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($experiment, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*')
;

$app->get('/medium-articles', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.medium-article-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $articles = $app['medium-articles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

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

$app->get('/people', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.person-list+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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
        }

        $person = $app['people'][$id];

        $accepts = [
            'application/vnd.elife.person+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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
        unset($episode['chapters']);

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
        }

        $episode = $app['podcast-episodes'][$number];

        $accepts = [
            'application/vnd.elife.podcast-episode+json; version=1',
        ];

        /** @var Accept $type */
        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        return new Response(
            json_encode($episode, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*')
;

$app->get('/search', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.search+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $for = strtolower(trim($request->query->get('for')));

    $sort = $request->query->get('sort', 'relevance');
    $subjects = (array) $request->query->get('subject', []);
    $types = (array) $request->query->get('type', []);

    $results = [];

    foreach ($app['articles'] as $result) {
        $result = $result['versions'][count($result['versions']) - 1];
        $result['_search'] = strtolower(json_encode($result));

        unset($result['issue']);
        unset($result['copyright']);
        unset($result['researchOrganisms']);
        unset($result['keywords']);
        unset($result['relatedArticles']);
        unset($result['abstract']);
        unset($result['digest']);
        unset($result['body']);
        unset($result['decisionLetter']);
        unset($result['authorResponse']);

        $results[] = $result;
    }

    foreach ($app['blog-articles'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['content']);
        $result['type'] = 'blog-article';
        $results[] = $result;
    }

    foreach ($app['collections'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['curators']);
        unset($result['content']);
        unset($result['relatedContent']);
        unset($result['podcastEpisodes']);
        $result['type'] = 'collection';
        $results[] = $result;
    }

    foreach ($app['events'] as $result) {
        if (DateTimeImmutable::createFromFormat(DATE_ATOM, $result['ends']) <= new DateTimeImmutable()) {
            continue;
        }

        $result['_search'] = strtolower(json_encode($result));
        unset($result['content']);
        unset($result['venue']);
        $result['type'] = 'event';
        $results[] = $result;
    }

    foreach ($app['experiments'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['content']);
        $result['type'] = 'labs-experiment';
        $results[] = $result;
    }

    foreach ($app['interviews'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['interviewee']['cv']);
        unset($result['content']);
        $result['type'] = 'interview';
        $results[] = $result;
    }

    foreach ($app['podcast-episodes'] as $result) {
        $result['_search'] = strtolower(json_encode($result));
        unset($result['chapters']);
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
    foreach (
        [
            'correction',
            'editorial',
            'feature',
            'insight',
            'research-advance',
            'research-article',
            'research-exchange',
            'retraction',
            'registered-report',
            'replication-study',
            'short-report',
            'tools-resources',
        ] as $articleType
    ) {
        $allTypes[$articleType] = count(array_filter($results, function ($result) use ($articleType) {
            return $articleType === $result['type'];
        }));
    }

    foreach (
        [
            'blog-article',
            'collection',
            'event',
            'labs-experiment',
            'interview',
            'podcast-episode',
        ] as $contentType
    ) {
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
            if ('event' === $a['type']) {
                $aDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $a['starts']);
            } elseif ('collection' === $a['type']) {
                $aDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $a['updated']);
            } else {
                $aDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
            }
            if ('event' === $b['type']) {
                $bDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $b['starts']);
            } elseif ('collection' === $b['type']) {
                $bDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $b['updated']);
            } else {
                $bDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $b['published']);
            }

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

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

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
    }

    $subject = $app['subjects'][$id];

    $accepts = [
        'application/vnd.elife.subject+json; version=1',
    ];

    /** @var Accept $type */
    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    return new Response(
        json_encode($subject, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
    );
});

$app->get('images/{type}/{file}/{extension}',
    function (Request $request, string $type, string $file, string $extension) use ($app) {
        $width = $request->query->get('width');
        $height = $request->query->get('height');

        if ($width > 5000 || $height > 5000) {
            throw new BadRequestHttpException('Too big');
        }

        $cacheKey = md5(implode('|', [$type, $file, $extension, $width, $height]));

        $cache = $app['filesystem'];

        if (false === $cache->has($cacheKey)) {
            $imagine = $app['imagine'];

            try {
                /** @var ImageInterface $image */
                $image = $imagine->open(__DIR__.'/../assets/'.$type.'/'.$file.'.'.$extension);
            } catch (Exception $e) {
                throw new NotFoundHttpException('Image not found', $e);
            }

            if ($width && $height) {
                if ($height > $image->getSize()->getHeight()) {
                    $image = $image->resize($image->getSize()->heighten($height));
                }
                if ($width > $image->getSize()->getWidth()) {
                    $image = $image->resize($image->getSize()->widen($width));
                }
                $image = $image->thumbnail(new Box($width, $height), ImageInterface::THUMBNAIL_OUTBOUND);
            } elseif ($width) {
                $image = $image->resize($image->getSize()->widen($width));
            } elseif ($height) {
                $image = $image->resize($image->getSize()->heighten($height));
            }

            $cache->put($cacheKey, $image->get($extension));
        }

        switch ($extension) {
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            default:
                throw new RuntimeException('Unknown extension '.$extension);
        }

        return new StreamedResponse(
            function () use ($cache, $cacheKey) {
                $image = $cache->readStream($cacheKey);

                while (!feof($image)) {
                    $buffer = fread($image, 1024);
                    echo $buffer;
                    flush();
                }
                fclose($image);
            },
            Response::HTTP_OK,
            ['Content-Type' => $contentType]
        );
    })->assert('number', '[1-9][0-9]*')->assert('width', '[1-9][0-9]*')->assert('height', '[1-9][0-9]*')
;

$app->after(function (Request $request, Response $response, Application $app) {
    if ($response instanceof StreamedResponse) {
        return;
    }

    $content = $response->getContent();

    $response->setContent(str_replace('%base_url%', $request->getSchemeAndHttpHost().$request->getBasePath(),
        $content));
});

$app->error(function (Throwable $e) {
    if ($e instanceof HttpExceptionInterface) {
        $status = $e->getStatusCode();
        $message = $e->getMessage();
        $extra = [];
    } elseif ($e instanceof UnsupportedVersion) {
        $status = Response::HTTP_NOT_ACCEPTABLE;
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
