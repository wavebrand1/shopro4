<?php

declare(strict_types=1);

namespace App\Installer\Presentation\Http;

use App\Installer\Application\InstallationManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 512)]
final readonly class InstallationRedirectSubscriber
{
    public function __construct(private InstallationManager $installer) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if ($path === '/install' || str_starts_with($path, '/install/')) return;
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) return;
        if ($this->installer->isInstalled()) return;

        $event->setResponse(new RedirectResponse('/install'));
    }
}
