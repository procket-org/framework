<?php

namespace Procket\Framework\Extensions\Twig;

use Procket\Framework\Procket;
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
            new TwigFunction('__', [Procket::instance(), 'trans']),
            new TwigFunction('trans', [Procket::instance(), 'trans']),
            new TwigFunction('__n', [Procket::instance(), 'transChoice']),
            new TwigFunction('trans_choice', [Procket::instance(), 'transChoice']),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('__', [Procket::instance(), 'trans'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('trans', [Procket::instance(), 'trans'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('__n', [Procket::instance(), 'transChoice'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]),
            new TwigFilter('trans_choice', [Procket::instance(), 'transChoice'], [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ])
        ];
    }
}