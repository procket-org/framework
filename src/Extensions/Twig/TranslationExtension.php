<?php

namespace Pocket\Framework\Extensions\Twig;

use Pocket\Framework\Pocket;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TranslationExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', [Pocket::instance(), 'trans']),
            new TwigFunction('trans', [Pocket::instance(), 'trans']),
            new TwigFunction('__n', [Pocket::instance(), 'transChoice']),
            new TwigFunction('trans_choice', [Pocket::instance(), 'transChoice']),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('__', [Pocket::instance(), 'trans'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('trans', [Pocket::instance(), 'trans'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('__n', [Pocket::instance(), 'transChoice'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('trans_choice', [Pocket::instance(), 'transChoice'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ])
        ];
    }
}