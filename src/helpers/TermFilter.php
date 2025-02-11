<?php

namespace eLife\DummyApi\helpers;

class TermFilter
{
    public static function isSignificanceTermFound(array $result, string $term)
    {
        if (isset($result['elifeAssessment'])) {
            return in_array($term, $result['elifeAssessment']['significance']);
        }
        return false;
    }
};
