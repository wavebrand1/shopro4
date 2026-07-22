<?php

declare(strict_types=1);

namespace App\Module\Presentation\Http;

use App\Module\Application\ModuleRuntime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener(event: 'kernel.controller')]
final readonly class ModuleRuntimeSubscriber
{
    public function __construct(private ModuleRuntime $runtime) {}

    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            [$instance, $method] = $controller;
            $methodAttributes = (new \ReflectionMethod($instance, $method))->getAttributes(RequiresModule::class);
            $classAttributes = (new \ReflectionClass($instance))->getAttributes(RequiresModule::class);
        } elseif (is_object($controller)) {
            $methodAttributes = (new \ReflectionMethod($controller, '__invoke'))->getAttributes(RequiresModule::class);
            $classAttributes = (new \ReflectionClass($controller))->getAttributes(RequiresModule::class);
        } else {
            return;
        }
        $attribute = $methodAttributes[0] ?? $classAttributes[0] ?? null;
        if ($attribute === null) return;
        $requirement = $attribute->newInstance();
        if (!$this->runtime->isEnabled($requirement->code)) throw new NotFoundHttpException();
    }
}
