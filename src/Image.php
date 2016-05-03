<?php

namespace eLife\Labs;

use Assert\Assertion;

final class Image
{
    private $uri;

    private $altText;

    private $mediaType;

    private $ratio;

    private $width;

    public function __construct(
        string $uri,
        string $altText,
        string $mediaType,
        string $ratio,
        int $width
    ) {
        Assertion::url($uri);
        Assertion::regex($mediaType, '/^[\w.+-]+\/[\w.+-]+$/');
        Assertion::regex($ratio, '/^[0-9]*[1-9]:[0-9]*[1-9]$/');
        Assertion::min($width, 1);

        $this->uri = $uri;
        $this->altText = $altText;
        $this->mediaType = $mediaType;
        $this->ratio = $ratio;
        $this->width = $width;
    }

    public function getUri() : string
    {
        return $this->uri;
    }

    public function getAltText() : string
    {
        return $this->altText;
    }

    public function getMediaType() : string
    {
        return $this->mediaType;
    }

    public function getRatio() : string
    {
        return $this->ratio;
    }

    public function getWidth() : int
    {
        return $this->width;
    }
}