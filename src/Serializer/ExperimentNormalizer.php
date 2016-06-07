<?php

namespace eLife\Labs\Serializer;

use eLife\Labs\Blocks\Block;
use eLife\Labs\Blocks\Image;
use eLife\Labs\Blocks\Paragraph;
use eLife\Labs\Blocks\Section;
use eLife\Labs\Blocks\YouTube;
use eLife\Labs\Experiment;
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
            'image' => [
                'alt' => $object->getImage()->getAltText(),
                'source' => [
                    'ratio' => $object->getImage()->getRatio(),
                    'types' => [
                        $object->getImage()->getMediaType() => [
                            $object->getImage()
                                ->getWidth() => $object->getImage()->getUri(),
                        ],
                    ],
                ],
            ],
        ];

        if (empty($context['partial'])) {
            foreach ($object->getContent() as $block) {
                $return['content'][] = $this->serializeBlock($block);
            }
        }

        if (!empty($object->getImpactStatement())) {
            $return['impactStatement'] = $object->getImpactStatement();
        }

        if ($object->isHighlighted()) {
            $return['isHighlighted'] = true;
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
                    'image' => [
                        $block->getImage()->getRatio() => [
                            '0' => [
                                $block->getImage()->getMediaType() => [
                                    $block->getImage()
                                        ->getWidth() => $block->getImage()
                                        ->getUri(),
                                ],
                            ],
                        ],
                    ],
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
