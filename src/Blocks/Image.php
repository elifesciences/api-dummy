<?php

namespace eLife\Labs\Blocks;

use Assert\Assertion;

final class Image extends Block
{
    private $alt;

    private $uri;

    private $caption;

    public function __construct(string $alt, string $uri, string $caption = null)
    {
        Assertion::url($uri);

        $this->alt = $alt;
        $this->uri = $uri;
        $this->caption = $caption;
    }

    public function getAlt() : string
    {
        return $this->alt;
    }

    public function getUri() : string
    {
        return $this->uri;
    }

    /**
     * @return string|null
     */
    public function getCaption()
    {
        return $this->caption;
    }
}
