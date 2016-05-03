<?php

namespace eLife\Labs\Serializer;

use eLife\Labs\Blocks\Image;
use eLife\Labs\Blocks\Paragraph;
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
            'content' => [],
        ];

        foreach ($object->getContent() as $block) {
            switch (get_class($block)) {
                case Paragraph::class:
                    $return['content'][] = [
                        'type' => 'paragraph',
                        'version' => 1,
                        'text' => $block->getText(),
                    ];
                    break;
                case Image::class:
                    $return['content'][] = [
                        'type' => 'image',
                        'version' => 2,
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
                    break;
                default:
                    throw new RuntimeException('Unknown block type ' . get_class($block));
            }
        }

        if (!empty($object->getImpactStatement())) {
            $return['impactStatement'] = $object->getImpactStatement();
        }

        if ($object->isHighlighted()) {
            $return['isHighlighted'] = true;
        }

        if (1 === $context['version']) {
            $return['foo'] = 'bar';
        } else {
            $return['foo'] = ['bar' => 'baz'];
        }

        return $return;
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof Experiment && 'json' === $format;
    }
}
