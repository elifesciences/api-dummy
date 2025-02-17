<?php

use eLife\ApiProblem\Silex\ApiProblemProvider;
use eLife\ContentNegotiator\Silex\ContentNegotiationProvider;
use eLife\DummyApi\endpoints\Search;
use eLife\DummyApi\helpers\ArticleSnippet;
use eLife\Ping\Silex\PingControllerProvider;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Negotiation\Accept;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once __DIR__.'/../vendor/autoload.php';

$dataDirSet = getenv('DATA_FOLDER');
$dataDir = __DIR__.'/../'.($dataDirSet ? $dataDirSet : 'data');
$dataCheck = !((bool) $dataDirSet);

$app = new Application();

$app->register(new ApiProblemProvider());
$app->register(new ContentNegotiationProvider());
$app->register(new CorsServiceProvider(), ['cors.allowOrigin' => '*']);
$app->register(new PingControllerProvider());

$app['cors-enabled']($app);

$grabData = function (string $subFolder, callable $prepareData) use ($dataDir, $dataCheck) {
    $data = [];

    try {
        $finder = (new Finder())->files()->name('*.json')->in($dataDir.'/'.$subFolder);

        $data = $prepareData($finder);
    } catch (Throwable $e) {
        if ($dataCheck) {
            throw $e;
        }
    };

    return $data;
};

$app['annotations'] = function () use ($grabData) {
    return $grabData('annotations', function (Finder $finder) {
        $annotations = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $annotations[$file->getBasename('.json')] = $json;
        }

        return $annotations;
    });
};

$app['annual-reports'] = function () use ($grabData) {
    return $grabData('annual-reports', function (Finder $finder) {
        $reports = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $reports[$json['year']] = $json;
        }

        ksort($reports);

        return $reports;
    });
};

$app['articles'] = function () use ($grabData) {
    return $grabData('articles', function (Finder $finder) {
        $articles = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            foreach ($json['versions'] as $version) {
                if (isset($version['id'])) {
                    $articles[$version['id']] = $json;
                }
            }
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
                if (isset($version['version']) && null === $article1Dates[$version['status']]) {
                    $article1Dates[$version['status']] = DateTimeImmutable::createFromFormat(DATE_ATOM,
                        $version['published']);
                }
            }

            foreach ($article2['versions'] as $version) {
                if (isset($version['version']) && null === $article2Dates[$version['status']]) {
                    $article2Dates[$version['status']] = DateTimeImmutable::createFromFormat(DATE_ATOM,
                        $version['published']);
                }
            }

            $article1Date = $article1Dates['vor'] ?? $article1Dates['poa'];
            $article2Date = $article2Dates['vor'] ?? $article2Dates['poa'];

            return $article1Date <=> $article2Date;
        });

        return $articles;
    });
};

$app['bioprotocols'] = function () use ($grabData) {
    return $grabData('bioprotocols', function (Finder $finder) {
        $bioprotocols = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $bioprotocols[$file->getBasename('.json')] = $json;
        }

        ksort($bioprotocols);

        return $bioprotocols;
    });
};

$app['blog-articles'] = function () use ($grabData) {
    return $grabData('blog-articles', function (Finder $finder) {
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
    });
};

$app['reviewed-preprints'] = function () use ($grabData) {
    return $grabData('reviewed-preprints', function (Finder $finder) {
        $preprints = [];
        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $preprints[$json['id']] = $json;
        }

        uasort($preprints, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
        });

        return $preprints;
    });
};

$app['collections'] = function () use ($grabData) {
    return $grabData('collections', function (Finder $finder) {
        $collections = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $collections[$json['id']] = $json;
        }

        uasort($collections, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $b['updated'] ?? $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['updated'] ?? $a['published']);
        });

        return $collections;
    });
};

$app['covers'] = function () use ($grabData) {
    return $grabData('covers', function (Finder $finder) {
        $covers = [];

        foreach ($finder as $file) {
            $covers[] = json_decode($file->getContents(), true);
        }

        uasort($covers, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $a['item']['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $b['item']['published']);
        });

        return $covers;
    });
};

$app['digests'] = function () use ($grabData) {
    return $grabData('digests', function (Finder $finder) {
        $digests = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $digests[$json['id']] = $json;
        }

        $dateFactory = function (array $item) : DateTimeImmutable {
            return DateTimeImmutable::createFromFormat(
                DATE_ATOM,
                $item['published'] ?? '2038-01-01T00:00:00Z'
            );
        };
        uasort($digests, function (array $a, array $b) use ($dateFactory) {
            return $dateFactory($b) <=> $dateFactory($a);
        });

        return $digests;
    });
};

$app['events'] = function () use ($grabData) {
    return $grabData('events', function (Finder $finder) {
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
    });
};

$app['job-adverts'] = function () use ($grabData) {
    return $grabData('job-adverts', function (Finder $finder) {
        $adverts = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $adverts[$json['id']] = $json;
        }

        uasort($adverts, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
        });

        return $adverts;
    });
};

$app['labs'] = function () use ($grabData) {
    return $grabData('labs', function (Finder $finder) {
        $labs = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $labs[$json['id']] = $json;
        }

        ksort($labs);

        return $labs;
    });
};

$app['highlights'] = function () use ($grabData) {
    return $grabData('highlights', function (Finder $finder) {
        $highlights = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $highlights[$file->getBasename('.json')] = $json;
        }

        ksort($highlights);

        return $highlights;
    });
};

$app['interviews'] = function () use ($grabData) {
    return $grabData('interviews', function (Finder $finder) {
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
    });
};

$app['metrics'] = function () use ($grabData) {
    return $grabData('metrics', function (Finder $finder) {
        $items = [];

        foreach ($finder as $file) {
            $name = explode('-', $file->getBasename('.json'));

            $json = json_decode($file->getContents(), true);
            $items[$name[0]][$name[1]] = $json;
        }

        return $items;
    });
};

$app['people'] = function () use ($grabData) {
    return $grabData('people', function (Finder $finder) {
        $people = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $people[$json['id']] = $json;
        }

        uasort($people, function (array $a, array $b) {
            return $a['name']['index'] <=> $b['name']['index'];
        });

        return $people;
    });
};

$app['podcast-episodes'] = function () use ($grabData) {
    return $grabData('podcast-episodes', function (Finder $finder) {
        $episodes = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $episodes[(int) $json['number']] = $json;
        }

        ksort($episodes);

        return $episodes;
    });
};

$app['press-packages'] = function () use ($grabData) {
    return $grabData('press-packages', function (Finder $finder) {
        $packages = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $packages[$json['id']] = $json;
        }

        uasort($packages, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['published']);
        });

        return $packages;
    });
};

$app['profiles'] = function () use ($grabData) {
    return $grabData('profiles', function (Finder $finder) {
        $profiles = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $profiles[$json['id']] = $json;
        }

        uasort($profiles, function (array $a, array $b) {
            return $a['name']['index'] <=> $b['name']['index'];
        });

        return $profiles;
    });
};

$app['recommendations'] = function () use ($grabData) {
    return $grabData('recommendations', function (Finder $finder) {
        $recommendations = [];

        foreach ($finder as $file) {
            $name = explode('-', $file->getBasename('.json'));

            $json = json_decode($file->getContents(), true);
            $recommendations[$name[0]][$name[1]] = $json;
        }

        return $recommendations;
    });
};

$app['promotional-collections'] = function () use ($grabData) {
    return $grabData('promotional-collections', function (Finder $finder) {
        $promotionalCollections = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $promotionalCollections[$json['id']] = $json;
        }

        uasort($promotionalCollections, function (array $a, array $b) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM,
                    $b['updated'] ?? $b['published']) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $a['updated'] ?? $a['published']);
        });

        return $promotionalCollections;
    });
};

$app['subjects'] = function () use ($grabData) {
    return $grabData('subjects', function (Finder $finder) {
        $subjects = [];

        foreach ($finder as $file) {
            $json = json_decode($file->getContents(), true);
            $subjects[$json['id']] = $json;
        }

        ksort($subjects);

        return $subjects;
    });
};

$app->get('/annotations', function (Request $request, Accept $type) use ($app) {
    $annotations = $app['annotations'];

    if (empty($request->query->get('by'))) {
        throw new BadRequestHttpException('Invalid by parameter');
    }

    if (false === isset($annotations[$request->query->get('by')])) {
        throw new NotFoundHttpException('Not found');
    }

    $annotations = $annotations[$request->query->get('by')];

    if ('restricted' !== $request->query->get('access')) {
        $annotations = array_filter($annotations, function (array $annotation) {
            return 'public' === $annotation['access'];
        });
    }

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($annotations),
        'items' => [],
    ];

    $useDate = $request->query->get('use-date', 'updated');

    uasort($annotations, function (array $a, array $b) use ($useDate) {
        if ('created' === $useDate) {
            $aDate = $a['created'];
            $bDate = $b['created'];
        } else {
            $aDate = $a['updated'] ?? $a['created'];
            $bDate = $b['updated'] ?? $b['created'];
        }

        return DateTimeImmutable::createFromFormat(DATE_ATOM, $bDate) <=> DateTimeImmutable::createFromFormat(DATE_ATOM, $aDate);
    });

    if ('asc' === $request->query->get('order', 'desc')) {
        $annotations = array_reverse($annotations);
    }

    $annotations = array_slice($annotations, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($annotations) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    $content['items'] = $annotations;

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.annotation-list+json; version=1'
));

$app->get('/annual-reports', function (Request $request, Accept $type) use ($app) {
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
        $content['items'][] = $report;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.annual-report-list+json; version=2'
));

$app->get('/annual-reports/{year}',
    function (Accept $type, int $year) use ($app) {
        if (false === isset($app['annual-reports'][$year])) {
            throw new NotFoundHttpException('Not found');
        }

        $report = $app['annual-reports'][$year];

        return new Response(
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.annual-report+json; version=2'
))->assert('number', '[1-9][0-9]*');

$app->get('/articles', function (Request $request, Accept $type) use ($app) {
    $articles = $app['articles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    if ($request->query->has('subject')) {
        $articles = array_filter($articles, function (array $article) use ($request) : bool {
            // @FIXME: improve this so that latest version works regardless of order in array.
            $latestVersion = $article['versions'][count($article['versions']) - 1];

            return count(array_intersect((array) $request->query->get('subject'), array_map(function (array $subject) {
                return $subject['id'];
            }, $latestVersion['subjects'] ?? [])));
        });
    }

    $content = [
        'total' => count($articles),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $articles = array_reverse($articles);
    }

    $articles = array_slice($articles, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($articles) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($articles as $i => $article) {
        $latestVersion = $article['versions'][count($article['versions']) - 1];
        $latestVersion = ArticleSnippet::prepare($latestVersion);

        $content['items'][] = $latestVersion;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.article-list+json; version=1'
));

$app->get('/articles/{number}',
    function (Request $request, string $number) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $latestVersion = 1;
        foreach ($app['articles'][$number]['versions'] as $version) {
            if (isset($version['version']) && $version['version'] > $latestVersion) {
                $latestVersion = $version['version'];
            }
        }

        $subRequest = Request::create('/articles/'.$number.'/versions/'.$latestVersion, 'GET', [],
            $request->cookies->all(), [], $request->server->all());

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }
);

$app->get('/articles/{number}/versions',
    function (Accept $type, string $number) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $article = $app['articles'][$number];

        if (!empty($article['received'])) {
            $content = [
                'received' => $article['received'],
                'accepted' => $article['accepted'],
            ];
        }

        if (!empty($article['sentForReview'])) {
            $content['sentForReview'] = $article['sentForReview'];
        }

        $content['versions'] = [];
        foreach ($article['versions'] as $articleVersion) {
            if ($type->getParameter('version') > 1 || !empty($articleVersion['version'])) {
                if (!empty($articleVersion['version'])) {
                    $articleVersion = ArticleSnippet::prepare($articleVersion);
                }

                $content['versions'][] = $articleVersion;
            }
        }

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.article-history+json; version=2',
    'application/vnd.elife.article-history+json; version=1'
));

$app->get('/articles/{number}/versions/{version}',
    function (Request $request, string $number, int $version) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }

        $article = $app['articles'][$number];

        $found = false;
        foreach ($article['versions'] as $articleVersion) {
            if (!$found && isset($articleVersion['version']) && $version === $articleVersion['version']) {
                $found = $articleVersion;
            }
        }

        if (false === $found) {
            throw new NotFoundHttpException('Version not found');
        }

        $articleVersion = $found;

        if ('vor' === $articleVersion['status']) {
            $accepts = [
                'application/vnd.elife.article-vor+json; version=8',
                'application/vnd.elife.article-vor+json; version=7',
            ];
        } else {
            $accepts = [
                'application/vnd.elife.article-poa+json; version=4',
                'application/vnd.elife.article-poa+json; version=3',
            ];
        }

        $app['content_negotiator.accept']->negotiate($request, $accepts);
        $type = $request->attributes->get(ContentNegotiationProvider::ATTRIBUTE_ACCEPT);

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ('55774' === $number && 'vor' === $articleVersion['status'] && $type->getParameter('version') < 8) {
            throw new NotAcceptableHttpException('This article VoR requires version 8.');
        }

        if ('poa' === $articleVersion['status'] && in_array($type->getParameter('version'), [3])) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
        }

        if ('vor' === $articleVersion['status'] && in_array($type->getParameter('version'), [7])) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
        }

        return new Response(
            json_encode($articleVersion, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
);

$app->get('/articles/{number}/related',
    function (Accept $type, string $number) use ($app) {
        if (false === isset($app['articles'][$number])) {
            throw new NotFoundHttpException('Article not found');
        }
        $article = $app['articles'][$number];
        $headers = ['Content-Type' => $type->getNormalizedValue()];
        $content = $article['relatedArticles'] ?? [];

        if ($type->getParameter('version') < 2 && '13410' === $number) {
            throw new NotAcceptableHttpException('These article relations require version 2.');
        }

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.article-related+json; version=2',
    'application/vnd.elife.article-related+json; version=1'
));

$app->get('/bioprotocol/{contentType}/{id}', function (Accept $type, string $contentType, string $id) use ($app) {
    if (false === isset($app['bioprotocols']["$contentType-$id"])) {
        throw new NotFoundHttpException('Not found');
    }

    $bioprotocols = $app['bioprotocols']["$contentType-$id"];

    $content = [
        'total' => count($bioprotocols),
        'items' => $bioprotocols,
    ];

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.bioprotocol+json; version=1'
));

$app->get('/blog-articles', function (Request $request, Accept $type) use ($app) {
    $articles = $app['blog-articles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $articles = array_filter($articles, function ($article) use ($subjects) {
            $articleSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $article['subjects'] ?? []);

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
        unset($article['image']);
        unset($article['content']);

        $content['items'][] = $article;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.blog-article-list+json; version=1'
));

$app->get('/blog-articles/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['blog-articles'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $article = $app['blog-articles'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 2) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));

            if (in_array($id, ['359325', '369365', '378207'])) {
                throw new NotAcceptableHttpException('This blog article requires version 2.');
            }
        }

        return new Response(
            json_encode($article, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.blog-article+json; version=2',
    'application/vnd.elife.blog-article+json; version=1'
));

$app->get('/collections', function (Request $request, Accept $type) use ($app) {
    $collections = $app['collections'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);
    $subjects = (array) $request->query->get('subject', []);
    $containing = (array) $request->query->get('containing', []);

    if (false === empty($subjects)) {
        $collections = array_filter($collections, function ($collection) use ($subjects) {
            $collectionSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $collection['subjects'] ?? []);

            return count(array_intersect($subjects, $collectionSubjects));
        });
    }

    if (false === empty($containing)) {
        $collections = array_filter($collections, function ($collection) use ($containing) {
            foreach ($collection['content'] as $item) {
                if (in_array("{$item['type']}/{$item['id']}", $containing)) {
                    return true;
                }
            }

            return false;
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
        unset($collection['summary']);
        unset($collection['content']);
        unset($collection['relatedContent']);
        unset($collection['podcastEpisodes']);
        unset($collection['image']['banner']);
        unset($collection['image']['social']);

        $content['items'][] = $collection;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.collection-list+json; version=1'
));

$app->get('/collections/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['collections'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $collection = $app['collections'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 3) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));

            if ('with-reviewed-preprint' === $id) {
                throw new NotAcceptableHttpException('This collection requires version 3.');
            }
        }

        return new Response(
            json_encode(array_filter($collection), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.collection+json; version=3',
    'application/vnd.elife.collection+json; version=2'
));

$app->get('/community', function (Request $request, Accept $type) use ($app) {
    $addType = function ($type) {
        return function ($item) use ($type) {
            $item['type'] = $type;

            return $item;
        };
    };
    $items = array_merge(
        array_map($addType('interview'), $app['interviews']),
        array_map($addType('labs-post'), $app['labs'])
    );
    usort($items, function ($a, $b) {
        return $a['published'] >= $b['published'] ? -1 : 1;
    });

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($items),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $items = array_reverse($items);
    }

    $items = array_slice($items, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($items) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($items as $i => $item) {
        unset($item['interviewee']['cv']);
        unset($item['content']);
        unset($item['image']['banner']);

        $content['items'][] = $item;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.community-list+json; version=1'
));

$app->get('/covers', function (Request $request, Accept $type) use ($app) {
    $covers = $app['covers'];

    $useDate = $request->query->get('use-date', 'default');
    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $originalStartDate = $request->query->get('start-date', '2000-01-01'), new DateTimeZone('Z'));
    $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $originalEndDate = $request->query->get('end-date', '2999-12-31'), new DateTimeZone('Z'));

    if (!$startDate || $startDate->format('Y-m-d') !== $originalStartDate) {
        throw new BadRequestHttpException('Invalid start date');
    } elseif (!$endDate || $endDate->format('Y-m-d') !== $originalEndDate) {
        throw new BadRequestHttpException('Invalid end date');
    }

    $startDate = $startDate->setTime(0, 0, 0);
    $endDate = $endDate->setTime(23, 59, 59);

    if ($endDate < $startDate) {
        throw new BadRequestHttpException('End date must be on or after start date');
    }

    foreach ($covers as $i => $cover) {
        if ('published' === $useDate) {
            $covers[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $cover['item']['published']);
        } elseif (!empty($cover['item']['statusDate'])) {
            $covers[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $cover['item']['statusDate']);
        } elseif ('collection' === $cover['item']['type'] && !empty($cover['item']['updated'])) {
            $covers[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $cover['item']['updated']);
        } else {
            $covers[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $cover['item']['published']);
        }
    }

    uasort($covers, function (array $a, array $b) {
        return $a['_sort_date'] <=> $b['_sort_date'];
    });

    $covers = array_filter($covers, function ($result) use ($startDate) {
        return $result['_sort_date'] >= $startDate;
    });

    $covers = array_filter($covers, function ($result) use ($endDate) {
        return $result['_sort_date'] <= $endDate;
    });

    $content = [
        'total' => count($covers),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $covers = array_reverse($covers);
    }

    $covers = array_slice($covers, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($covers) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($covers as $i => $cover) {
        unset($cover['_sort_date']);

        $content['items'][] = $cover;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.cover-list+json; version=1'
));

$app->get('/covers/current', function (Accept $type) use ($app) {
    $covers = array_slice(array_reverse($app['covers']), 0, 4);

    $content = [
        'total' => count($covers),
        'items' => [],
    ];

    foreach ($covers as $i => $report) {
        unset($report['content']);

        $content['items'][] = $report;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.cover-list+json; version=1'
));

$app->get('/digests', function (Request $request, Accept $type) use ($app) {
    $digests = $app['digests'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $digests = array_filter($digests, function ($digest) use ($subjects) {
            $digestSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $digest['subjects'] ?? []);

            return count(array_intersect($subjects, $digestSubjects));
        });
    }

    $content = [
        'total' => count($digests),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $digests = array_reverse($digests);
    }

    $digests = array_slice($digests, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($digests) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($digests as $i => $digest) {
        unset($digest['content']);
        unset($digest['relatedContent']);

        $content['items'][] = $digest;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.digest-list+json; version=1'
));

$app->get('/digests/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['digests'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $digest = $app['digests'][$id];

        return new Response(
            json_encode($digest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.digest+json; version=1'
));

$app->get('/events', function (Request $request, Accept $type) use ($app) {
    $events = $app['events'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $show = $request->query->get('show', 'all');

    $now = new DateTimeImmutable();

    if ('open' === $show) {
        $events = array_filter($events, function ($event) use ($now) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM, $event['ends']) > $now;
        });
    } elseif ('closed' === $show) {
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
        unset($event['image']);
        unset($event['content']);
        unset($event['venue']);

        $content['items'][] = $event;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.event-list+json; version=1'
));

$app->get('/events/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['events'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $events = $app['events'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 2) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
        }

        return new Response(
            json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.event+json; version=2',
    'application/vnd.elife.event+json; version=1'
));

$app->get('/highlights/{list}', function (Request $request, Accept $type, string $list) use ($app) {
    if (false === isset($app['highlights'][$list])) {
        throw new NotFoundHttpException('Not found');
    }

    $highlights = array_filter($app['highlights'][$list], function ($item) use ($type) {
        return ($type->getParameter('version') > 1 || 'digest' !== $item['item']['type']) &&
            ($type->getParameter('version') > 2 || 'press-package' !== $item['item']['type']);
    });

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($highlights),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $highlights = array_reverse($highlights);
    }

    $highlights = array_slice($highlights, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($highlights) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    $content['items'] = $highlights;

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    if ($type->getParameter('version') < 3) {
        $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
    }

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.highlight-list+json; version=3',
    'application/vnd.elife.highlight-list+json; version=2'
));

$app->get('/interviews', function (Request $request, Accept $type) use ($app) {
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
        unset($interview['image']['social']);
        if (empty($interview['image'])) {
            unset($interview['image']);
        }

        $content['items'][] = $interview;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.interview-list+json; version=1'
));

$app->get('/interviews/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['interviews'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $interview = $app['interviews'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 2) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
        }

        return new Response(
            json_encode($interview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.interview+json; version=2',
    'application/vnd.elife.interview+json; version=1'
));

$app->get('/job-adverts', function (Request $request, Accept $type) use ($app) {
    $jobAdverts = $app['job-adverts'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $show = $request->query->get('show', 'all');

    $now = new DateTimeImmutable();

    if ('open' === $show) {
        $jobAdverts = array_filter($jobAdverts, function ($event) use ($now) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM, $event['closingDate']) > $now;
        });
    } elseif ('closed' === $show) {
        $jobAdverts = array_filter($jobAdverts, function ($event) use ($now) {
            return DateTimeImmutable::createFromFormat(DATE_ATOM, $event['closingDate']) <= $now;
        });
    }

    $content = [
        'total' => count($jobAdverts),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $jobAdverts = array_reverse($jobAdverts);
    }

    $jobAdverts = array_slice($jobAdverts, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($jobAdverts) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($jobAdverts as $i => $jobAdvert) {
        $content['items'][] = $jobAdvert;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.job-advert-list+json; version=1'
));

$app->get('/job-adverts/{id}', function (Accept $type, string $id) use ($app) {
    if (false === isset($app['job-adverts'][$id])) {
        throw new NotFoundHttpException('Not found');
    }

    $jobAdverts = $app['job-adverts'][$id];

    return new Response(
        json_encode($jobAdverts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        ['Content-Type' => $type->getNormalizedValue()]
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.job-advert+json; version=1'
));

$app->get('/labs-posts', function (Request $request, Accept $type) use ($app) {
    $labs = $app['labs'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($labs),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $labs = array_reverse($labs);
    }

    $labs = array_slice($labs, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($labs) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($labs as $i => $lab) {
        unset($lab['content']);
        unset($lab['image']['banner']);
        unset($lab['image']['social']);

        $content['items'][] = $lab;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    if ($request->query->get('foo')) {
        $headers['Warning'] = '299 elifesciences.org "Deprecation: `foo` query string parameter will be removed, use `bar` instead"';
    }

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.labs-post-list+json; version=1'
));

$app->get('/labs-posts/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['labs'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $lab = $app['labs'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 2) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));

            if ('80000003' === $id) {
                throw new NotAcceptableHttpException('This labs post requires version 2.');
            }
        }

        return new Response(
            json_encode($lab, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.labs-post+json; version=2',
    'application/vnd.elife.labs-post+json; version=1'
));

$app->get('/metrics/{contentType}/{id}/citations',
    function (Accept $type, string $contentType, string $id) use ($app) {
        if (false === isset($app['metrics'][$contentType][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $headers = ['Content-Type' => $type->getNormalizedValue()];
        $content = $app['metrics'][$contentType][$id]['citations'];

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.metric-citations+json; version=1'
));

$app->get('/metrics/{contentType}/{id}/{metric}',
    function (Request $request, Accept $type, string $contentType, string $id, string $metric) use ($app) {
        if (false === isset($app['metrics'][$contentType][$id]) || !in_array($metric, ['page-views', 'downloads'])) {
            throw new NotFoundHttpException('Not found');
        }

        if ('page-views' === $metric) {
            $metric = $app['metrics'][$contentType][$id]['pageViews'];
        } else {
            $metric = $app['metrics'][$contentType][$id]['downloads'];
        }

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 10);

        if ('month' === $request->query->get('by', 'month')) {
            $months = [];
            foreach ($metric as $day => $value) {
                $month = substr($day, 0, 7);
                if (!isset($months[$month])) {
                    $months[$month] = 0;
                }

                $months[$month] = $months[$month] + $value;
            }

            $metric = $months;
        }

        $content = [
            'totalValue' => array_reduce($metric, function (int $carry, int $value) {
                return $carry + $value;
            }, 0),
            'totalPeriods' => count($metric),
            'periods' => [],
        ];

        if ('desc' === $request->query->get('order', 'desc')) {
            $metric = array_reverse($metric);
        }

        $metric = array_slice($metric, ($page * $perPage) - $perPage, $perPage);

        if (0 === count($metric) && $page > 1) {
            throw new NotFoundHttpException('No page '.$page);
        }

        $content['periods'] = array_map(function (string $period, int $value) {
            return compact('period', 'value');
        }, array_keys($metric), array_values($metric));

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.metric-time-period+json; version=1'
));

$app->get('/people', function (Request $request, Accept $type) use ($app) {
    $people = $app['people'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $personTypes = (array) $request->query->get('type', []);
    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($personTypes)) {
        $people = array_filter($people, function ($person) use ($personTypes) {
            return in_array($person['type']['id'], $personTypes);
        });
    }

    if (false === empty($subjects)) {
        $people = array_filter($people, function ($person) use ($subjects) {
            $personSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $person['research']['expertises'] ?? []);

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

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.person-list+json; version=1'
));

$app->get('/people/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['people'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $person = $app['people'][$id];

        return new Response(
            json_encode($person, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.person+json; version=1'
));

$app->get('/podcast-episodes', function (Request $request, Accept $type) use ($app) {
    $episodes = $app['podcast-episodes'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $episodes = array_filter($episodes, function ($episode) use ($subjects) {
            $episodeSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $episode['subjects'] ?? []);

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
        unset($episode['image']['banner']);
        unset($episode['image']['social']);

        $content['items'][] = $episode;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.podcast-episode-list+json; version=1'
));

$app->get('/podcast-episodes/{number}',
    function (Accept $type, int $number) use ($app) {
        if (false === isset($app['podcast-episodes'][$number])) {
            throw new NotFoundHttpException('Not found');
        }

        $episode = $app['podcast-episodes'][$number];

        return new Response(
            json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.podcast-episode+json; version=1'
))->assert('number', '[1-9][0-9]*');

$app->get('/press-packages', function (Request $request, Accept $type) use ($app) {
    $packages = $app['press-packages'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $subjects = (array) $request->query->get('subject', []);

    if (false === empty($subjects)) {
        $packages = array_filter($packages, function ($article) use ($subjects) {
            $articleSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $article['subjects'] ?? []);

            return count(array_intersect($subjects, $articleSubjects));
        });
    }

    $content = [
        'total' => count($packages),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $packages = array_reverse($packages);
    }

    $packages = array_slice($packages, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($packages) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($packages as $i => $package) {
        unset($package['content']);
        unset($package['relatedContent']);
        unset($package['mediaContacts']);
        unset($package['about']);
        unset($package['image']);

        $content['items'][] = $package;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.press-package-list+json; version=1'
));

$app->get('/press-packages/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['press-packages'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $packages = $app['press-packages'][$id];

        $headers = ['Content-Type' => $type->getNormalizedValue()];

        if ($type->getParameter('version') < 4) {
            $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));

            if ('6b266861' === $id) {
                throw new NotAcceptableHttpException('This press package requires version 4.');
            }
        }

        return new Response(
            json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            $headers
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.press-package+json; version=4',
    'application/vnd.elife.press-package+json; version=3'
));

$app->get('/profiles', function (Request $request, Accept $type) use ($app) {
    $profiles = $app['profiles'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($profiles),
        'items' => [],
    ];

    if ('desc' === $request->query->get('order', 'desc')) {
        $profiles = array_reverse($profiles);
    }

    $profiles = array_slice($profiles, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($profiles) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($profiles as $i => $profile) {
        $content['items'][] = $profile;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.profile-list+json; version=1'
));

$app->get('/profiles/{id}',
    function (Accept $type, string $id) use ($app) {
        if (false === isset($app['profiles'][$id])) {
            throw new NotFoundHttpException('Not found');
        }

        $profile = $app['profiles'][$id];

        return new Response(
            json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => $type->getNormalizedValue()]
        );
    }
)->before($app['negotiate.accept'](
    'application/vnd.elife.profile+json; version=1'
));

$app->get('/promotional-collections', function (Request $request, Accept $type) use ($app) {
    $promotionalCollections = $app['promotional-collections'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);
    $subjects = (array) $request->query->get('subject', []);
    $containing = (array) $request->query->get('containing', []);

    if (false === empty($subjects)) {
        $promotionalCollections = array_filter($promotionalCollections, function ($promotionalCollection) use ($subjects) {
            $promotionalCollectionsSubjects = array_map(function (array $subject) {
                return $subject['id'];
            }, $promotionalCollection['subjects'] ?? []);

            return count(array_intersect($subjects, $promotionalCollectionsSubjects));
        });
    }

    if (false === empty($containing)) {
        $promotionalCollections = array_filter($promotionalCollections, function ($promotionalCollection) use ($containing) {
            foreach ($promotionalCollection['content'] as $item) {
                if (in_array("{$item['type']}/{$item['id']}", $containing)) {
                    return true;
                }
            }

            return false;
        });
    }

    $content = [
        'total' => count($promotionalCollections),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $promotionalCollections = array_reverse($promotionalCollections);
    }

    $promotionalCollections = array_slice($promotionalCollections, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($promotionalCollections) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($promotionalCollections as $i => $promotionalCollection) {
        unset($promotionalCollection['editors']);
        unset($promotionalCollection['summary']);
        unset($promotionalCollection['content']);
        unset($promotionalCollection['relatedContent']);
        unset($promotionalCollection['podcastEpisodes']);
        unset($promotionalCollection['image']['banner']);
        unset($promotionalCollection['image']['social']);

        $content['items'][] = $promotionalCollection;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.promotional-collection-list+json; version=1'
));

$app->get('/promotional-collections/{id}', function (Accept $type, string $id) use ($app) {
    if (false === isset($app['promotional-collections'][$id])) {
        throw new NotFoundHttpException('Not found');
    }

    $promotionalCollection = $app['promotional-collections'][$id];

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    if ($type->getParameter('version') < 2) {
        $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));

        if ('highlights-japan' === $id) {
            throw new NotAcceptableHttpException('This promotional collection requires version 2.');
        }
    }

    return new Response(
        json_encode(array_filter($promotionalCollection), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.promotional-collection+json; version=2',
    'application/vnd.elife.promotional-collection+json; version=1'
));

$app->get('/recommendations/{contentType}/{id}', function (Request $request, Accept $type, string $contentType, string $id) use ($app) {
    if (false === isset($app['recommendations'][$contentType][$id])) {
        throw new NotFoundHttpException('Not found');
    }

    $recommendations = $app['recommendations'][$contentType][$id];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($recommendations),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $recommendations = array_reverse($recommendations);
    }

    $recommendations = array_slice($recommendations, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($recommendations) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    $content['items'] = $recommendations;

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    if ($type->getParameter('version') < 3 && '13410' === $id) {
        throw new NotAcceptableHttpException('This recommendation requires version 3.');
    }

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.recommendations+json; version=3',
    'application/vnd.elife.recommendations+json; version=2'
));

$app->get('/reviewed-preprints', function(Request $request, Accept $type) use ($app){
    $reviewedPreprints = $app['reviewed-preprints'];

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $useDate = $request->query->get('use-date', 'default');
    $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $originalStartDate = $request->query->get('start-date', '2000-01-01'), new DateTimeZone('Z'));
    $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $originalEndDate = $request->query->get('end-date', '2999-12-31'), new DateTimeZone('Z'));

    if (!$startDate || $startDate->format('Y-m-d') !== $originalStartDate) {
        throw new BadRequestHttpException('Invalid start date');
    } elseif (!$endDate || $endDate->format('Y-m-d') !== $originalEndDate) {
        throw new BadRequestHttpException('Invalid end date');
    }
    $startDate = $startDate->setTime(0, 0, 0);
    $endDate = $endDate->setTime(23, 59, 59);

    if ($endDate < $startDate) {
        throw new BadRequestHttpException('End date must be on or after start date');
    }

    if ('asc' === $request->query->get('order', 'desc')) {
        $reviewedPreprints = array_reverse($reviewedPreprints);
    }

    $content = [
        'total' => count($reviewedPreprints),
        'items' => [],
    ];
    $reviewedPreprints = array_slice($reviewedPreprints, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($reviewedPreprints) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($reviewedPreprints as $i => $reviewedPreprint) {
        if ('published' === $useDate) {
            $reviewedPreprints[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $reviewedPreprint['published']);
        } elseif (!empty($reviewedPreprint['statusDate'])) {
            $reviewedPreprints[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $reviewedPreprint['statusDate']);
        } else {
            $reviewedPreprints[$i]['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $reviewedPreprint['published']);
        }
    }

    uasort($reviewedPreprints, function (array $a, array $b) {
        return $a['_sort_date'] <=> $b['_sort_date'];
    });

    $reviewedPreprints = array_filter($reviewedPreprints, function ($result) use ($startDate) {
        return $result['_sort_date'] >= $startDate;
    });

    $reviewedPreprints = array_filter($reviewedPreprints, function ($result) use ($endDate) {
        return $result['_sort_date'] <= $endDate;
    });

    $reviewedPreprints = array_slice($reviewedPreprints, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($reviewedPreprints) && $page > 1) {
        throw new NotFoundHttpException('No page '.$page);
    }

    foreach ($reviewedPreprints as $id => $reviewedPreprint) {
        unset($reviewedPreprint['indexContent']);
        unset($reviewedPreprint['_sort_date']);

        $content['items'][] = $reviewedPreprint;
    }

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.reviewed-preprint-list+json;version=1'
));

$app->get('/reviewed-preprints/{id}', function(Accept $type, $id) use ($app) {
    if (false === isset($app['reviewed-preprints'][$id])) {
        throw new NotFoundHttpException('Reviewed preprint not found');
    }

    $content = $app['reviewed-preprints'][$id];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        ['Content-Type' => $type->getNormalizedValue()]
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.reviewed-preprint+json;version=1'
));

$app->get('/subjects', function (Request $request, Accept $type) use ($app) {
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

    $headers = ['Content-Type' => $type->getNormalizedValue()];

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        $headers
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.subject-list+json; version=1'
));

$app->get('/subjects/{id}', function (Accept $type, string $id) use ($app) {
    if (false === isset($app['subjects'][$id])) {
        throw new NotFoundHttpException('Not found');
    }

    $subject = $app['subjects'][$id];

    return new Response(
        json_encode($subject, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        Response::HTTP_OK,
        ['Content-Type' => $type->getNormalizedValue()]
    );
})->before($app['negotiate.accept'](
    'application/vnd.elife.subject+json; version=1'
));

$app->get('/oauth2/authorize', function (Request $request) {
    $redirectUri = $request->get('redirect_uri');
    $state = $request->get('state');
    $code = 'code_'.$state;
    $location = $redirectUri.'?'.http_build_query([
        'code' => $code,
        'state' => $state,
    ]);

    return new Response(
        'pong',
        Response::HTTP_FOUND,
        [
            'Location' => $location,
        ]
    );
});

$app->post('/oauth2/token', function (Request $request) {
    $code = $request->get('code');

    return new JsonResponse([
        'access_token' => 'access_token_'.$code,
        'token_type' => 'bearer',
        'expires_in' => 30 * 24 * 60 * 60,
        'scope' => '/authenticate',
        'id' => 'jcarberry',
        'orcid' => '0000-0002-1825-0097',
        'name' => 'Josiah Carberry',
    ]);
});

$app->after(function (Request $request, Response $response, Application $app) {
    if ($response instanceof StreamedResponse) {
        return;
    }

    $content = $response->getContent();

    $response->setContent(str_replace('%base_url%', $request->getSchemeAndHttpHost().$request->getBasePath(),
        $content));

    $response->headers->set('ETag', md5($response->getContent()));
    $response->isNotModified($request);
});

$app = Search::add($app);

return $app;
