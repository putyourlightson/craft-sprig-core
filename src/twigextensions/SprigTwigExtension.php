<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcore\twigextensions;

use putyourlightson\sprigcore\SprigCore;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class SprigTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sprig', [SprigCore::getInstance()->components, 'create']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        return [
            'sprig' => SprigCore::$sprigVariable,
        ];
    }
}
