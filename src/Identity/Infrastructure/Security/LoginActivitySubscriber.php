<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginActivitySubscriber
{
    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly SiteUserRepository $siteUsers,
        private readonly AuditLogRepository $logs,
    ) {}

    #[AsEventListener(event: LoginSuccessEvent::class, dispatcher: 'security.event_dispatcher.admin')]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if ($event->getFirewallName() !== 'admin' || !$event->getUser() instanceof AdminUser) return;

        $user = $event->getUser();
        $user->recordLogin();
        $this->users->save($user);
        $this->writeLog(new AuditLog(
            'user',
            'login_success',
            'Poprawne logowanie do panelu administracyjnego.',
            $user->getUserIdentifier(),
            $event->getRequest()->getClientIp(),
        ));
    }

    #[AsEventListener(event: LoginSuccessEvent::class, dispatcher: 'security.event_dispatcher.frontend')]
    public function onSiteLoginSuccess(LoginSuccessEvent $event): void
    {
        if ($event->getFirewallName() !== 'frontend' || !$event->getUser() instanceof SiteUser) return;
        $user = $event->getUser();
        $user->recordLogin();
        $this->siteUsers->save($user);
        $this->writeLog(new AuditLog('site_user', 'login_success', 'Poprawne logowanie użytkownika witryny.', $user->getUserIdentifier(), $event->getRequest()->getClientIp()));
    }

    #[AsEventListener(event: LoginFailureEvent::class, dispatcher: 'security.event_dispatcher.admin')]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if ($event->getFirewallName() !== 'admin') return;

        $identifier = trim((string) $event->getRequest()->request->get('_username'));
        $this->writeLog(new AuditLog(
            'user',
            'login_failure',
            'Nieudana próba logowania do panelu administracyjnego.',
            $identifier !== '' ? mb_substr($identifier, 0, 180) : null,
            $event->getRequest()->getClientIp(),
            [],
            true,
        ));
    }

    #[AsEventListener(event: LoginFailureEvent::class, dispatcher: 'security.event_dispatcher.frontend')]
    public function onSiteLoginFailure(LoginFailureEvent $event): void
    {
        if ($event->getFirewallName() !== 'frontend') return;
        $identifier = trim((string) $event->getRequest()->request->get('_username'));
        $this->writeLog(new AuditLog('site_user', 'login_failure', 'Nieudana próba logowania użytkownika witryny.', $identifier !== '' ? mb_substr($identifier, 0, 180) : null, $event->getRequest()->getClientIp(), [], true));
    }

    private function writeLog(AuditLog $log): void
    {
        try {
            $this->logs->save($log);
        } catch (\Throwable) {
            // Rejestrowanie audytu nie może zablokować logowania podczas wdrożenia migracji.
        }
    }
}
