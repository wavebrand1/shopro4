<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use App\Module\Application\ModuleAvailability;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

#[AsEventListener(event: 'kernel.exception', priority: 32)]
final class PublicErrorSubscriber
{
    public function __construct(
        private readonly Environment $twig,
        private readonly PageRepository $pages,
        private readonly EntityManagerInterface $entityManager,
        private readonly ModuleAvailability $modules,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->modules->isEnabled('cms')) return;
        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() !== Response::HTTP_NOT_FOUND) return;

        $request = $event->getRequest();
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) return;
        $path = $request->getPathInfo();
        if (self::isPathSpace($path, '/admin') || self::isPathSpace($path, '/api')) return;
        if (!$request->getPreferredFormat() || !in_array($request->getPreferredFormat(), ['html', 'txt'], true)) return;

        try {
            $source = $this->pages->findPublishedErrorPage();
            $page = $source;
            $language = $request->attributes->get('_shopro_language');
            if ($source && $language instanceof Language && !$language->isDefaultLanguage()) {
                $translation = $this->entityManager->getRepository(PageTranslation::class)->findOneBy([
                    'page' => $source, 'language' => $language, 'published' => true,
                ]);
                if ($translation) $page = $translation;
            }
            $content = $this->twig->render('cms/error/404.html.twig', ['page' => $page, 'source_page' => $source]);
        } catch (\Throwable) {
            $content = '<!doctype html><html lang="pl"><meta charset="utf-8"><title>404</title><body><main><h1>404</h1><p>Strona nie istnieje.</p><a href="/">Wróć na stronę główną</a></main></body></html>';
        }

        $response = new Response($request->isMethod('HEAD') ? '' : $content, Response::HTTP_NOT_FOUND);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');
        $event->setResponse($response);
    }

    private static function isPathSpace(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
