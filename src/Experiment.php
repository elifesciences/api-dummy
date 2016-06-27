<?php

namespace eLife\Api;

use Assert\Assertion;
use DateTimeImmutable;
use DateTimeZone;
use eLife\Api\Blocks\Block;

final class Experiment
{
    private $number;

    private $title;

    private $published;

    private $image;

    private $content;

    private $impactStatement;

    private $highlighted;

    public function __construct(
        int $number,
        string $title,
        DateTimeImmutable $published,
        array $image,
        array $content,
        string $impactStatement = null,
        bool $highlighted = false
    ) {
        Assertion::min($number, 1);
        Assertion::notBlank($title);
        Assertion::notEmpty($content);
        Assertion::allIsInstanceOf($content, Block::class);

        $this->number = $number;
        $this->title = $title;
        $this->published = $published->setTimezone(new DateTimeZone('UTC'));
        $this->image = $image;
        $this->content = $content;
        $this->impactStatement = (string) $impactStatement;
        $this->highlighted = $highlighted;
    }

    public function getNumber() : int
    {
        return $this->number;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getPublished() : DateTimeImmutable
    {
        return $this->published;
    }

    public function getImage() : array
    {
        return $this->image;
    }

    /**
     * @return Block[]
     */
    public function getContent() : array
    {
        return $this->content;
    }

    public function getImpactStatement() : string
    {
        return $this->impactStatement;
    }

    public function isHighlighted() : bool
    {
        return $this->highlighted;
    }
}
