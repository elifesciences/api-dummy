<?php

namespace eLife\Labs\Blocks;

use eLife\Labs\Image as ImageItem;

final class Image extends Block
{
    private $image;

    private $caption;

    public function __construct(ImageItem $image, string $caption = null)
    {
        $this->image = $image;
        $this->caption = (string) $caption;
    }

    public function getImage() : ImageItem
    {
        return $this->image;
    }

    public function getCaption() : string
    {
        return $this->caption;
    }
}
