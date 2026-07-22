<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Language\Presentation\Http\LanguageContextSubscriber;
use App\Module\Application\ModuleAvailability;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ModuleEventRuntimeGuardTest extends TestCase
{
    public function testDisabledLanguageListenerDoesNotTouchPersistence(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');
        $modules = new class implements ModuleAvailability {
            public function isEnabled(string $code): bool { return false; }
        };
        $event = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/en/example'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new LanguageContextSubscriber($entityManager, $modules))($event);

        self::assertNull($event->getRequest()->attributes->get('_shopro_language'));
    }
}
