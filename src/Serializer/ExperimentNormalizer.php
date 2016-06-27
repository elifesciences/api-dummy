<?php

namespace eLife\Api\Serializer;

use DateTimeImmutable;
use eLife\Api\Blocks\Block;
use eLife\Api\Blocks\Image;
use eLife\Api\Blocks\Paragraph;
use eLife\Api\Blocks\Section;
use eLife\Api\Blocks\YouTube;
use eLife\Api\Experiment;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExperimentNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($object, $format = null, array $context = [])
    {
        /* @var Experiment $object */

        $return = [
            'number' => $object->getNumber(),
            'title' => $object->getTitle(),
            'published' => $object->getPublished()->format(DATE_RFC3339),
            'image' => $object->getImage(),
        ];

        if (empty($context['partial'])) {
            foreach ($object->getContent() as $block) {
                $return['content'][] = $this->normalizeBlock($block);
            }
        }

        if (!empty($object->getImpactStatement())) {
            $return['impactStatement'] = $object->getImpactStatement();
        }

        return $return;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $content = [];
        foreach ($data['content'] as $block) {
            $content[] = $this->denormalizeBlock($block);
        }

        $experiment = new Experiment(
            (int) $data['number'],
            $data['title'],
            DateTimeImmutable::createFromFormat(DATE_ATOM, $data['published']),
            $data['image'],
            $content,
            $data['impactStatement'] ?? null
        );

        return $experiment;
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof Experiment && 'json' === $format;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Experiment::class === $type && 'json' === $format;
    }

    private function normalizeBlock(Block $block) : array
    {
        switch (get_class($block)) {
            case Paragraph::class:
                return [
                    'type' => 'paragraph',
                    'text' => $block->getText(),
                ];
            case Image::class:
                return [
                    'type' => 'image',
                    'alt' => $block->getAlt(),
                    'uri' => $block->getUri(),
                    'caption' => $block->getCaption(),
                ];
            case Section::class:
                $section = [
                    'type' => 'section',
                    'title' => $block->getTitle(),
                ];

                foreach ($block->getContent() as $subBlock) {
                    $section['content'][] = $this->normalizeBlock($subBlock);
                }

                return $section;
            case YouTube::class:
                return [
                    'type' => 'youtube',
                    'id' => $block->getId(),
                    'width' => $block->getWidth(),
                    'height' => $block->getHeight(),
                ];
            default:
                throw new RuntimeException('Unknown block type ' . get_class($block));
        }
    }

    private function denormalizeBlock(array $data) : Block
    {
        switch ($type = ($data['type'] ?? 'unknown')) {
            case 'paragraph':
                return new Paragraph($data['text']);
            case 'image':
                return new Image($data['alt'], $data['uri'], $data['caption']);
            case 'section':
                $content = [];
                foreach ($data['content'] as $subBlock) {
                    $content[] = $this->denormalizeBlock($subBlock);
                }

                return new Section($data['title'], ...$content);
            case 'youtube':
                return new YouTube($data['id'], $data['width'], $data['height']);
            default:
                throw new RuntimeException('Unknown block type ' . $type);
        }
    }
}
