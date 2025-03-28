<?php

namespace test\eLife\DummyApi;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use Traversable;

final class SmokeTest extends PHPUnit_Framework_TestCase
{
    use SilexTestCase;

    public function requestProvider() : Traversable
    {
        yield $path = '/' => [
            $this->createRequest($path),
            'application/problem+json',
            404,
        ];

        yield $path = '/annotations' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/annotations') as $file) {
            yield $path = '/annotations?by='.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.annotation-list+json; version=1',
            ];
        }

        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/recommendations') as $file) {
            $name = explode('-', $file->getBasename('.json'));

            yield $path = "/recommendations/${name[0]}/${name[1]}" => [
                $this->createRequest($path),
                'application/vnd.elife.recommendations+json; version=3',
            ];

            if ('13410' === $name[1]) {
                yield $path = "/recommendations/${name[0]}/${name[1]}" => [
                    $this->createRequest($path, 'application/vnd.elife.recommendations+json; version=2'),
                    'application/problem+json',
                    406,
                ];
            }
        }

        yield '/annual-reports wrong version' => [
          $this->createRequest('/annual-reports', 'application/vnd.elife.annual-report-list+json; version=1'),
          'application/problem+json',
          406,
        ];

        yield '/annual-reports/2012 wrong version' => [
            $this->createRequest('/annual-reports/2012', 'application/vnd.elife.annual-report+json; version=1'),
            'application/problem+json',
            406,
        ];

        yield $path = '/annual-reports' => [
            $this->createRequest($path),
            'application/vnd.elife.annual-report-list+json; version=2',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/annual-reports') as $file) {
            yield $path = '/annual-reports/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.annual-report+json; version=2',
            ];
        }

        yield $path = '/articles' => [
            $this->createRequest($path),
            'application/vnd.elife.article-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/articles') as $file) {
            $path = '/articles/'.$file->getBasename('.json');
            switch ($file->getBasename('.json')) {
                case '55774':
                    $vorMinimum = 8;
                    break;
                default:
                    $poaMinimum = 3;
                    $vorMinimum = 7;
            }

            yield "{$path} version highest" => [
                $this->createRequest($path),
                [
                    'application/vnd.elife.article-poa+json; version=4',
                    'application/vnd.elife.article-vor+json; version=8',
                ],
            ];
            yield "{$path} version lowest" => [
                $this->createRequest($path, 'application/vnd.elife.article-poa+json; version='.$poaMinimum.', application/vnd.elife.article-vor+json; version='.$vorMinimum),
                [
                    'application/vnd.elife.article-poa+json; version='.$poaMinimum,
                    'application/vnd.elife.article-vor+json; version='.$vorMinimum,
                ],
                200,
                [
                    'application/vnd.elife.article-poa+json; version=3' => '299 elifesciences.org "Deprecation: Support for version 3 will be removed"',
                    'application/vnd.elife.article-vor+json; version=7' => '299 elifesciences.org "Deprecation: Support for version 7 will be removed"',
                ],
            ];

            $path = '/articles/'.$file->getBasename('.json').'/versions';
            yield $path => [
                $this->createRequest($path),
                'application/vnd.elife.article-history+json; version=2',
            ];
            yield "{$path} version 1" => [
                $this->createRequest($path, 'application/vnd.elife.article-history+json; version=1'),
                'application/vnd.elife.article-history+json; version=1',
            ];

            $path = '/articles/'.$file->getBasename('.json').'/versions/1';
            yield "{$path} wrong version" => [
                $this->createRequest($path, 'application/vnd.elife.article-poa+json; version=1, application/vnd.elife.article-vor+json; version=1'),
                'application/problem+json',
                406,
            ];

            yield "{$path} version highest" => [
                $this->createRequest($path),
                [
                    'application/vnd.elife.article-poa+json; version=4',
                    'application/vnd.elife.article-vor+json; version=8',
                ],
            ];
            yield "{$path} version lowest" => [
                $this->createRequest($path, 'application/vnd.elife.article-poa+json; version='.$poaMinimum.', application/vnd.elife.article-vor+json; version='.$vorMinimum),
                [
                    'application/vnd.elife.article-poa+json; version='.$poaMinimum,
                    'application/vnd.elife.article-vor+json; version='.$vorMinimum,
                ],
                200,
                [
                    'application/vnd.elife.article-poa+json; version=3' => '299 elifesciences.org "Deprecation: Support for version 3 will be removed"',
                    'application/vnd.elife.article-vor+json; version=7' => '299 elifesciences.org "Deprecation: Support for version 7 will be removed"',
                ],
            ];

            yield $path = '/articles/'.$file->getBasename('.json').'/related' => [
                $this->createRequest($path),
                'application/vnd.elife.article-related+json; version=2',
            ];

            if ('13410' === $file->getBasename('.json')) {
                yield $path = '/articles/'.$file->getBasename('.json').'/related' => [
                    $this->createRequest($path, 'application/vnd.elife.article-related+json; version=1'),
                    'application/problem+json',
                    406
                ];
            } else {
                yield $path = '/articles/'.$file->getBasename('.json').'/related' => [
                    $this->createRequest($path, 'application/vnd.elife.article-related+json; version=1'),
                    'application/vnd.elife.article-related+json; version=1',
                ];
            }
        }

        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/bioprotocols') as $file) {
            $parts = explode('-', $file->getBasename('.json'));

            yield $path = '/bioprotocol/'.$parts[0].'/'.$parts[1] => [
                $this->createRequest($path, 'application/vnd.elife.bioprotocol+json; version=1'),
                'application/vnd.elife.bioprotocol+json; version=1',
            ];
        }

        yield $path = '/blog-articles' => [
            $this->createRequest($path),
            'application/vnd.elife.blog-article-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/blog-articles') as $file) {
            $path = '/blog-articles/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.blog-article+json; version=2',
            ];
            if (!in_array($file->getBasename('.json'), ['359325', '369365', '378207'])) {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.blog-article+json; version=1'),
                    'application/vnd.elife.blog-article+json; version=1',
                    200,
                    [
                        'application/vnd.elife.blog-article+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                    ],
                ];
            } else {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.blog-article+json; version=1'),
                    'application/problem+json',
                    406,
                ];
            }
        }

        yield $path = '/collections' => [
            $this->createRequest($path),
            'application/vnd.elife.collection-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/collections') as $file) {
            $path = '/collections/'.$file->getBasename('.json');

            yield "{$path} version 3" => [
                $this->createRequest($path),
                'application/vnd.elife.collection+json; version=3',
            ];
            if ('with-reviewed-preprint' === $file->getBasename('.json')) {
                yield "{$path} version 2" => [
                    $this->createRequest($path, 'application/vnd.elife.collection+json; version=2'),
                    'application/problem+json',
                    406,
                ];
            } else {
                yield "{$path} version 2" => [
                    $this->createRequest($path, 'application/vnd.elife.collection+json; version=2'),
                    'application/vnd.elife.collection+json; version=2',
                    200,
                    [
                        'application/vnd.elife.collection+json; version=2' => '299 elifesciences.org "Deprecation: Support for version 2 will be removed"',
                    ],
                ];
            }
        }

        yield $path = '/community' => [
            $this->createRequest($path),
            'application/vnd.elife.community-list+json; version=1',
        ];

        yield $path = '/covers' => [
            $this->createRequest($path),
            'application/vnd.elife.cover-list+json; version=1',
        ];
        yield $path = '/covers?start-date=2017-01-01&end-date=2017-01-01' => [
            $this->createRequest($path),
            'application/vnd.elife.cover-list+json; version=1',
        ];
        yield $path = '/covers?start-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/covers?end-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/covers?start-date=2017-01-02&end-date=2017-01-01' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/covers/current' => [
            $this->createRequest($path),
            'application/vnd.elife.cover-list+json; version=1',
        ];

        yield $path = '/digests' => [
            $this->createRequest($path),
            'application/vnd.elife.digest-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/digests') as $file) {
            yield $path = '/digests/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.digest+json; version=1',
            ];
        }

        yield $path = '/events' => [
            $this->createRequest($path),
            'application/vnd.elife.event-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/events') as $file) {
            $path = '/events/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.event+json; version=2',
            ];
            yield "{$path} version 1" => [
                $this->createRequest($path, 'application/vnd.elife.event+json; version=1'),
                'application/vnd.elife.event+json; version=1',
                200,
                [
                    'application/vnd.elife.event+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                ],
            ];
        }

        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/highlights') as $file) {
            $path = '/highlights/'.$file->getBasename('.json');
            yield "{$path} version 3" => [
                $this->createRequest($path),
                'application/vnd.elife.highlight-list+json; version=3',
            ];
            yield "{$path} version 2" => [
                $this->createRequest($path, 'application/vnd.elife.highlight-list+json; version=2'),
                'application/vnd.elife.highlight-list+json; version=2',
                200,
                [
                    'application/vnd.elife.highlight-list+json; version=2' => '299 elifesciences.org "Deprecation: Support for version 2 will be removed"',
                ],
            ];
        }

        yield $path = '/interviews' => [
            $this->createRequest($path),
            'application/vnd.elife.interview-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/interviews') as $file) {
            $path = '/interviews/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.interview+json; version=2',
            ];
            yield "{$path} version 1" => [
                $this->createRequest($path, 'application/vnd.elife.interview+json; version=1'),
                'application/vnd.elife.interview+json; version=1',
                200,
                [
                    'application/vnd.elife.interview+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                ],
            ];
        }

        yield $path = '/job-adverts' => [
            $this->createRequest($path),
            'application/vnd.elife.job-advert-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/job-adverts') as $file) {
            yield $path = '/job-adverts/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.job-advert+json; version=1',
            ];
        }

        yield $path = '/labs-posts' => [
            $this->createRequest($path),
            'application/vnd.elife.labs-post-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/labs') as $file) {
            $path = '/labs-posts/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.labs-post+json; version=2',
            ];
            if ('80000003' !== $file->getBasename('.json')) {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.labs-post+json; version=1'),
                    'application/vnd.elife.labs-post+json; version=1',
                    200,
                    [
                        'application/vnd.elife.labs-post+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                    ],
                ];
            } else {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.labs-post+json; version=1'),
                    'application/problem+json',
                    406,
                ];
            }
        }

        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/metrics') as $file) {
            $parts = explode('-', $file->getBasename('.json'));

            yield $path = '/metrics/'.$parts[0].'/'.$parts[1].'/citations' => [
                $this->createRequest($path),
                'application/vnd.elife.metric-citations+json; version=1',
            ];
            yield $path = '/metrics/'.$parts[0].'/'.$parts[1].'/downloads' => [
                $this->createRequest($path),
                'application/vnd.elife.metric-time-period+json; version=1',
            ];
            yield $path = '/metrics/'.$parts[0].'/'.$parts[1].'/page-views' => [
                $this->createRequest($path),
                'application/vnd.elife.metric-time-period+json; version=1',
            ];
        }

        yield $path = '/people' => [
            $this->createRequest($path),
            'application/vnd.elife.person-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/people') as $file) {
            yield $path = '/people/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.person+json; version=1',
            ];
        }

        yield $path = '/podcast-episodes' => [
            $this->createRequest($path),
            'application/vnd.elife.podcast-episode-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/podcast-episodes') as $file) {
            yield $path = '/podcast-episodes/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.podcast-episode+json; version=1',
            ];
        }

        yield $path = '/press-packages' => [
            $this->createRequest($path),
            'application/vnd.elife.press-package-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/press-packages') as $file) {
            $path = '/press-packages/'.$file->getBasename('.json');

            yield "{$path} version 4" => [
                $this->createRequest($path),
                'application/vnd.elife.press-package+json; version=4',
            ];
            if ('6b266861' === $file->getBasename('.json')) {
                yield "{$path} version 3" => [
                    $this->createRequest($path, 'application/vnd.elife.press-package+json; version=3'),
                    'application/problem+json',
                    406,
                ];
            } else {
                yield "{$path} version 3" => [
                    $this->createRequest($path, 'application/vnd.elife.press-package+json; version=3'),
                    'application/vnd.elife.press-package+json; version=3',
                    200,
                    [
                        'application/vnd.elife.press-package+json; version=3' => '299 elifesciences.org "Deprecation: Support for version 3 will be removed"',
                    ],
                ];
            }
        }

        yield $path = '/profiles' => [
            $this->createRequest($path),
            'application/vnd.elife.profile-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/profiles') as $file) {
            yield $path = '/profiles/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.profile+json; version=1',
            ];
        }

        yield $path = '/promotional-collections' => [
            $this->createRequest($path),
            'application/vnd.elife.promotional-collection-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/promotional-collections') as $file) {
            $path = '/promotional-collections/'.$file->getBasename('.json');

            yield "{$path} version 2" => [
                $this->createRequest($path),
                'application/vnd.elife.promotional-collection+json; version=2',
            ];
            if ('highlights-japan' === $file->getBasename('.json')) {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.promotional-collection+json; version=1'),
                    'application/problem+json',
                    406,
                ];
            } else {
                yield "{$path} version 1" => [
                    $this->createRequest($path, 'application/vnd.elife.promotional-collection+json; version=1'),
                    'application/vnd.elife.promotional-collection+json; version=1',
                    200,
                    [
                        'application/vnd.elife.promotional-collection+json; version=1' => '299 elifesciences.org "Deprecation: Support for version 1 will be removed"',
                    ],
                ];
            }
        }

        yield $path = '/reviewed-preprints' => [
            $this->createRequest($path),
            'application/vnd.elife.reviewed-preprint-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/reviewed-preprints') as $file) {
            yield $path = '/reviewed-preprints/'.$file->getBasename('.json') => [
                $this->createRequest($path, 'application/vnd.elife.reviewed-preprint+json; version=1'),
                'application/vnd.elife.reviewed-preprint+json; version=1',
            ];
        }
        yield $path = '/reviewed-preprints?start-date=2017-01-01&end-date=2017-01-01' => [
            $this->createRequest($path),
            'application/vnd.elife.reviewed-preprint-list+json; version=1',
        ];
        yield $path = '/reviewed-preprints?start-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/reviewed-preprints?end-date=2017-02-29' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];
        yield $path = '/reviewed-preprints?start-date=2017-01-02&end-date=2017-01-01' => [
            $this->createRequest($path),
            'application/problem+json',
            400,
        ];


        yield $path = '/subjects' => [
            $this->createRequest($path),
            'application/vnd.elife.subject-list+json; version=1',
        ];
        foreach ((new Finder())->files()->name('*.json')->in(__DIR__.'/../data/subjects') as $file) {
            yield $path = '/subjects/'.$file->getBasename('.json') => [
                $this->createRequest($path),
                'application/vnd.elife.subject+json; version=1',
            ];
        }
    }
}
