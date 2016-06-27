<?php

namespace eLife\Api\Blocks;

use Assert\Assertion;

final class Paragraph extends Block
{
    private $text;

    public function __construct(string $text)
    {
        Assertion::notBlank($text);

        $this->text = $text;
    }

    public function getText() : string
    {
        return $this->text;
    }
}
