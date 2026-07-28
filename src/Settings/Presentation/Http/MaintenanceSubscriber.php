<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Http;

use App\Module\Application\ModuleAvailability;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

#[AsEventListener(event: 'kernel.request', priority: 20)]
final class MaintenanceSubscriber
{
    public function __construct(private readonly SettingsProvider $settings, private readonly Environment $twig, private readonly ModuleAvailability $modules) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (self::isPathSpace($path, '/install')) return;
        if (!$this->modules->isEnabled('settings') || !$this->settings->get('maintenance', false)) return;
        if (self::isPathSpace($path, '/admin') || self::isPathSpace($path, '/_') || $path === '/newsletter/unsubscribe') return;
        $event->setResponse(new Response($this->twig->render('cms/maintenance.html.twig', [
            'message' => $this->settings->get('maintenance_message'),
            'date' => $this->settings->get('maintenance_date'),
            'time' => $this->settings->get('maintenance_time'),
        ]), Response::HTTP_SERVICE_UNAVAILABLE, ['Retry-After' => '3600']));
    }

    private static function isPathSpace(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
