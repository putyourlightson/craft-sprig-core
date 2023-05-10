<?php

namespace putyourlightson\sprig\generators;

use Nette\PhpGenerator\PhpNamespace;
use craft\generator\BaseGenerator;
use putyourlightson\sprig\base\Component;

/**
 * Creates a new Sprig component.
 */
class SprigComponent extends BaseGenerator
{
    public function run(): bool
    {
        $className = $this->classNamePrompt('Component name:', [
            'required' => true,
        ]);

        $namespace = (new PhpNamespace('sprig\\components'))
            ->addUse(Component::class);

        $class = $this->createClass($className, Component::class, [
            self::CLASS_PROPERTIES => [
                '_template'
            ],
        ]);

        $namespace->add($class);

        $this->writePhpClass($namespace);
        $this->command->success("**Sprig component created!**");

        return true;
    }
}
