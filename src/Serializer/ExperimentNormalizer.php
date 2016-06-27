<?php

namespace eLife\Api\Serializer;

use eLife\Api\Blocks\Block;
use eLife\Api\Blocks\Image;
use eLife\Api\Blocks\Paragraph;
use eLife\Api\Blocks\Section;
use eLife\Api\Blocks\YouTube;
use eLife\Api\Experiment;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExperimentNormalizer implements NormalizerInterface
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
                $return['content'][] = $this->serializeBlock($block);
            }
        }

        if (!empty($object->getImpactStatement())) {
            $return['impactStatement'] = $object->getImpactStatement();
        }

        return $return;
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof Experiment && 'json' === $format;
    }

    private function serializeBlock(Block $block) : array
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
                    $section['content'][] = $this->serializeBlock($subBlock);
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
}
