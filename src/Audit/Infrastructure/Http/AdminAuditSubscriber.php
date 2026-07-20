<?php
declare(strict_types=1);

namespace App\Audit\Infrastructure\Http;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse', priority: -128)]
final class AdminAuditSubscriber
{
    public function __construct(private readonly AuditLogRepository $logs, private readonly SettingsProvider $settings, private readonly TokenStorageInterface $tokens) {}

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/admin') || in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) || $event->getResponse()->getStatusCode() >= 400) return;

        try {
            if (!$this->settings->get('logging', true)) return;
            $route = (string) $request->attributes->get('_route', 'admin_action');
            $user = $this->tokens->getToken()?->getUser();
            $username = $user instanceof UserInterface ? $user->getUserIdentifier() : null;
            $important = str_contains($route, 'delete') || str_contains($route, 'clear') || str_contains($route, 'revoke');
            $this->logs->save(new AuditLog(
                'admin',
                $route,
                sprintf('%s %s', $request->getMethod(), $request->getPathInfo()),
                $username,
                $request->getClientIp(),
                ['route' => $route],
                $important,
            ));
        } catch (\Throwable) {
            // Audyt nie może przerwać operacji administracyjnej, np. w trakcie wdrażania migracji.
        }
    }
}
