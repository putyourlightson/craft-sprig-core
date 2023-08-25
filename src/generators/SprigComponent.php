<?php

namespace putyourlightson\sprig\generators;

use craft\generator\BaseGenerator;
use craft\helpers\FileHelper;
use Nette\PhpGenerator\PhpNamespace;
use putyourlightson\sprig\base\Component;

/**
 * Creates a new Sprig component.
 */
class SprigComponent extends BaseGenerator
{
    private string $templatePath;

    public function run(): bool
    {
        $className = $this->classNamePrompt('Component name:', [
            'required' => true,
        ]);

        $this->templatePath = $this->command->prompt('Template path: (optional)', [
            'pattern' => '/^[a-z0-9_\/]*$/i',
        ]);

        $namespace = (new PhpNamespace('sprig\\components'))
            ->addUse(Component::class);

        $class = $this->createClass($className, Component::class, [
            self::CLASS_PROPERTIES => $this->properties(),
            self::CLASS_METHODS => $this->methods(),
        ]);

        $namespace->add($class);
        $class->setComment('Sprig component');

        $this->writePhpClass($namespace);

        $path = $this->basePath . '/' . $className . '.php';
        $path = FileHelper::relativePath($path);
        $this->command->success('**Sprig component successfully created at: ' . $path . '**');

        return true;
    }

    private function methods(): array
    {
        return [
            'render' => <<<PHP
// ...

return parent::render();
PHP,
        ];
    }

    private function properties(): array
    {
        return [
            '_template' => $this->templatePath,
        ];
    }
}
