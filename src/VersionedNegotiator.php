<?php

namespace eLife\DummyApi;

use Negotiation\Accept;
use Negotiation\AcceptHeader;
use Negotiation\Exception\InvalidMediaType;
use Negotiation\Negotiator;

final class VersionedNegotiator extends Negotiator
{
    public function getBest($header, array $priorities) : AcceptHeader
    {
        if (empty($header)) {
            $header = '*/*';
        }

        $match = parent::getBest($header, $priorities);

        try {
            $header = new Accept($header);
        } catch (InvalidMediaType $e) {
            $header = new Accept($priorities[0]);
        }

        if (null === $match) {
            $match = new Accept($priorities[0]);
        }

        if (false === $match->hasParameter('version') || false === $header->hasParameter('version')) {
            return $match;
        }

        if ($match->getType() === $header->getType() && $match->getParameter('version') !== $header->getParameter('version')) {
            throw new UnsupportedVersion($header->getNormalizedValue().' is not supported');
        }

        return $match;
    }
}
