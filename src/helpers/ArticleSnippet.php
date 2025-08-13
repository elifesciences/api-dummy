<?php

namespace eLife\DummyApi\helpers;

class ArticleSnippet
{
    public static function prepare(array $article) {
        foreach ([
                     'abstract',
                     'issue',
                     'copyright',
                     'authors',
                     'researchOrganisms',
                     'keywords',
                     'digest',
                     'body',
                     'decisionLetter',
                     'authorResponse',
                     'editorEvaluation',
                     'publicReviews',
                     'recommendationsForAuthors',
                     'reviewers',
                     'references',
                     'ethics',
                     'funding',
                     'additionalFiles',
                     'dataSets',
                     'acknowledgements',
                     'appendices',
                     '-related-articles-reviewed-preprints',
                 ] as $field) {
            unset($article[$field]);
        }
        unset($article['image']['banner']);
        if (empty($article['image'])) {
            unset($article['image']);
        }

        return $article;
    }
}
