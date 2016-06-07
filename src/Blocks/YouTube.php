<?php

namespace eLife\Labs\Blocks;

use Assert\Assertion;

final class YouTube extends Block
{
    private $id;
    private $width;
    private $height;

    public function __construct(string $id, int $width, int $height)
    {
        Assertion::notBlank($id);
        Assertion::min($width, 1);
        Assertion::min($height, 1);

        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getHeight() : int
    {
        return $this->height;
    }
}
