<?php

namespace eLife\DummyApi\endpoints;

use DateTimeImmutable;
use DateTimeZone;
use eLife\DummyApi\helpers\ArticleSnippet;
use Silex\Application;
use Negotiation\Accept;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Search
{
    private static function resultTermFilterNotApplicable(
        array $resultElifeAssessment,
        array $terms
    ) : bool
    {
        return (
            in_array('not-applicable', $terms) &&
            empty($resultElifeAssessment)
        );
    }

    private static function resultTermFilterNotAssigned(
        array $resultElifeAssessment,
        array $resultElifeAssessmentTerms,
        array $terms
    ) : bool
    {
        return (
            in_array('not-assigned', $terms) &&
            !empty($resultElifeAssessment) &&
            empty($resultElifeAssessmentTerms)
        );
    }
    
    private static function resultTermFilter(
        array $resultElifeAssessmentTerms,
        array $terms
    ) : bool
    {
        return count(array_intersect($terms, $resultElifeAssessmentTerms));
    }

    public static function filterByTerms(
        array $results,
        array $terms,
        string $termGroup
    ) : array
    {
        if (false === empty($terms)) {
            $results = array_filter($results, function ($result) use ($termGroup, $terms) {
                $resultElifeAssessment = $result['elifeAssessment'] ?? [];
                $resultElifeAssessmentTerms = $resultElifeAssessment[$termGroup] ?? [];
                return
                    self::resultTermFilterNotApplicable($resultElifeAssessment, $terms)
                    ||
                    self::resultTermFilterNotAssigned($resultElifeAssessment, $resultElifeAssessmentTerms, $terms)
                    ||
                    self::resultTermFilter($resultElifeAssessmentTerms, $terms);
            });
        }

        return $results;
    }

    public static function add(Application $app)
    {
        $app->get('/search', function (Request $request, Accept $type) use ($app) {
            $page = $request->query->get('page', 1);
            $perPage = $request->query->get('per-page', 10);
        
            $for = strtolower(trim($request->query->get('for')));
        
            $useDate = $request->query->get('use-date', 'default');
            $sort = $request->query->get('sort', 'relevance');
            $subjects = (array) $request->query->get('subject', []);
            $types = (array) $request->query->get('type', []);
            $elifeAssessmentSignificances = (array) $request->query->get('elifeAssessmentSignificance', []);
            $elifeAssessmentStrengths = (array) $request->query->get('elifeAssessmentStrength', []);
        
            $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $requestStartDate = $request->query->get('start-date', '2000-01-01'), new DateTimeZone('Z'));
            $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $requestEndDate = $request->query->get('end-date', '2999-12-31'), new DateTimeZone('Z'));
        
            if (!$startDate || $startDate->format('Y-m-d') !== $requestStartDate) {
                throw new BadRequestHttpException('Invalid start date');
            } elseif (!$endDate || $endDate->format('Y-m-d') !== $requestEndDate) {
                throw new BadRequestHttpException('Invalid end date');
            }
        
            $startDate = $startDate->setTime(0, 0, 0);
            $endDate = $endDate->setTime(23, 59, 59);
        
            if ($endDate < $startDate) {
                throw new BadRequestHttpException('End date must be on or after start date');
            }
        
            $results = [];
        
            foreach ($app['articles'] as $result) {
                $latest = null;
                foreach ($result['versions'] as $articleVersion) {
                    if (isset($articleVersion['version']) && (!$latest || $latest['version'] < $articleVersion['version'])) {
                        $latest = $articleVersion;
                    }
                }
                if ($type->getParameter('version') === '2' && isset($app['reviewed-preprints'][$latest['id']])) {
                    $reviewedPreprint = $app['reviewed-preprints'][$latest['id']];
                    $latest['reviewedDate'] = $reviewedPreprint['reviewedDate'];
                    if (!empty($reviewedPreprint['curationLabels'])) {
                        $latest['curationLabels'] = $reviewedPreprint['curationLabels'];
                    }
                }
                $result = $latest;
                $result['_search'] = strtolower(json_encode($latest));
                $result = ArticleSnippet::prepare($result);
        
                if ('published' === $useDate) {
                    $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
                } else {
                    $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['statusDate'] ?? date(DATE_ATOM));
                }
        
                $results[] = $result;
            }
        
            $contentTypes = [
                'blog-article',
                'collection',
                'labs-post',
                'interview',
                'podcast-episode',
            ];
        
            if ($type->getParameter('version') === '2') {
                foreach ($app['reviewed-preprints'] as $result) {
                    if (!isset($app['articles'][$result['id']])) {
                        $result['_search'] = strtolower(json_encode($result));
                        unset($result['indexContent']);
                        $result['type'] = 'reviewed-preprint';
                        $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['statusDate']);
                        $results[] = $result;
                    }
                }
                $contentTypes[] = 'reviewed-preprint';
            }
        
            foreach ($app['blog-articles'] as $result) {
                $result['_search'] = strtolower(json_encode($result));
                unset($result['content']);
                unset($result['image']);
                $result['type'] = 'blog-article';
                $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
                $results[] = $result;
            }
        
            foreach ($app['collections'] as $result) {
                $result['_search'] = strtolower(json_encode($result));
                unset($result['curators']);
                unset($result['summary']);
                unset($result['content']);
                unset($result['relatedContent']);
                unset($result['podcastEpisodes']);
                unset($result['image']['banner']);
                unset($result['image']['social']);
                $result['type'] = 'collection';
                if ('published' === $useDate) {
                    $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
                } else {
                    $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['updated'] ?? $result['published']);
                }
                $results[] = $result;
            }
        
            foreach ($app['labs'] as $result) {
                $result['_search'] = strtolower(json_encode($result));
                unset($result['content']);
                unset($result['image']['banner']);
                unset($result['image']['social']);
                $result['type'] = 'labs-post';
                $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
                $results[] = $result;
            }
        
            foreach ($app['interviews'] as $result) {
                $result['_search'] = strtolower(json_encode($result));
                unset($result['interviewee']['cv']);
                unset($result['content']);
                unset($result['image']['social']);
                if (empty($result['image'])) {
                    unset($result['image']);
                }
                $result['type'] = 'interview';
                $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
                $results[] = $result;
            }
        
            foreach ($app['podcast-episodes'] as $result) {
                $result['_search'] = strtolower(json_encode($result));
                unset($result['chapters']);
                unset($result['image']['banner']);
                unset($result['image']['social']);
                $result['type'] = 'podcast-episode';
                $result['_sort_date'] = DateTimeImmutable::createFromFormat(DATE_ATOM, $result['published']);
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
                        return in_array($subject['id'], array_map(function (array $subject) {
                            return $subject['id'];
                        }, $result['subjects'] ?? []));
                    })),
                ];
            });
        
            $allTypeKeys = [
                'correction',
                'editorial',
                'expression-concern',
                'feature',
                'insight',
                'research-advance',
                'research-article',
                'research-communication',
                'retraction',
                'registered-report',
                'replication-study',
                'review-article',
                'scientific-correspondence',
                'short-report',
                'tools-resources',
            ];
        
            if ($type->getParameter('version') === '2') {
                $allTypeKeys[] = 'reviewed-preprint';
            }
            $allTypes = [];
            foreach (
              $allTypeKeys as $articleType
            ) {
                $allTypes[$articleType] = count(array_filter($results, function ($result) use ($articleType) {
                    return $articleType === $result['type'];
                }));
            }
        
            foreach ($contentTypes as $contentType) {
                $allTypes[$contentType] = count(array_filter($results, function ($result) use ($contentType) {
                    return $contentType === $result['type'];
                }));
            }
        
            if (false === empty($types)) {
                $results = array_filter($results, function ($result) use ($types) {
                    return in_array($result['type'], $types);
                });
            }
            
            if (false === empty($elifeAssessmentSignificances)) {
                $results = self::filterByTerms($results, $elifeAssessmentSignificances, 'significance');
            }

            if (false === empty($elifeAssessmentStrengths)) {
                $results = self::filterByTerms($results, $elifeAssessmentStrengths, 'strength');
            }
        
            if (false === empty($subjects)) {
                $results = array_filter($results, function ($result) use ($subjects) {
                    return count(array_intersect($subjects, array_map(function (array $subject) {
                        return $subject['id'];
                    }, $result['subjects'] ?? [])));
                });
            }
        
            $results = array_filter($results, function ($result) use ($startDate) {
                return $result['_sort_date'] >= $startDate;
            });
        
            $results = array_filter($results, function ($result) use ($endDate) {
                return $result['_sort_date'] <= $endDate;
            });
        
            $content = [
                'total' => count($results),
                'items' => [],
                'subjects' => $allSubjects,
                'types' => $allTypes,
            ];
        
            if ('date' === $sort) {
                usort($results, function (array $a, array $b) {
                    return $b['_sort_date'] <=> $a['_sort_date'];
                });
            }
        
            if ('asc' === $request->query->get('order', 'desc')) {
                $results = array_reverse($results);
            }
        
            $results = array_slice($results, ($page * $perPage) - $perPage, $perPage);
        
            if (0 === count($results) && $page > 1) {
                throw new NotFoundHttpException('No page '.$page);
            }
        
            $content['items'] = array_map(function (array $result) {
                unset($result['_sort_date']);
        
                return $result;
            }, $results);
        
            $headers = ['Content-Type' => $type->getNormalizedValue()];
        
            if ($type->getParameter('version') < 2) {
                $headers['Warning'] = sprintf('299 elifesciences.org "Deprecation: Support for version %d will be removed"', $type->getParameter('version'));
            }
        
            return new Response(
                json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                Response::HTTP_OK,
                $headers
            );
        })->before($app['negotiate.accept'](
            'application/vnd.elife.search+json; version=2',
            'application/vnd.elife.search+json; version=1'
        ));

        return $app;
    }
}
