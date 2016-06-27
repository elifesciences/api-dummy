<?php

namespace eLife\Api\Blocks;

use Assert\Assertion;

final class Section extends Block
{
    private $title;

    private $content;

    public function __construct(string $title, Block ...$content)
    {
        Assertion::notBlank($title);

        $this->title = $title;
        $this->content = $content;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getContent() : array
    {
        return $this->content;
    }
}