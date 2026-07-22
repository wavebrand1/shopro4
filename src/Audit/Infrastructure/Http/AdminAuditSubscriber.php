<?php
declare(strict_types=1);

namespace App\Audit\Infrastructure\Http;

use App\Audit\Application\AdminAuditOperation;
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
        $path = $request->getPathInfo();
        if (($path !== '/admin' && !str_starts_with($path, '/admin/')) || in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) || $event->getResponse()->getStatusCode() >= 400) return;

        try {
            if (!$this->settings->get('logging', true)) return;
            $route = (string) $request->attributes->get('_route', 'admin_action');
            $operation = AdminAuditOperation::normalize($request->request->get('action'));
            $user = $this->tokens->getToken()?->getUser();
            $username = $user instanceof UserInterface ? $user->getUserIdentifier() : null;
            $data = ['route' => $route, 'method' => $request->getMethod()];
            if ($operation !== null) $data['operation'] = $operation;
            if ($route === 'admin_file_manager_index') {
                foreach (['path', 'item'] as $key) {
                    $value = trim((string) $request->request->get($key, ''));
                    if ($value !== '') $data[$key] = mb_substr($value, 0, 255);
                }
            }
            $this->logs->save(new AuditLog(
                'admin',
                AdminAuditOperation::action($route, $operation),
                sprintf('%s %s%s', $request->getMethod(), $request->getPathInfo(), $operation !== null ? ' ['.$operation.']' : ''),
                $username,
                $request->getClientIp(),
                $data,
                AdminAuditOperation::isImportant($route, $operation),
            ));
        } catch (\Throwable) {
            // Audyt nie może przerwać operacji administracyjnej, np. w trakcie wdrażania migracji.
        }
    }
}
