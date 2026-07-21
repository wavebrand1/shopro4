<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Cms\Infrastructure\Persistence\Doctrine\UrlRedirectRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener(event: 'kernel.exception', priority: 64)]
final class LegacyUrlRedirectSubscriber
{
    public function __construct(private readonly UrlRedirectRepository $redirects, private readonly PageRepository $pages) {}
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getThrowable() instanceof NotFoundHttpException) return;
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) return;
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) return;

        try {
            $redirect = $this->redirects->findActive($path);
            if ($redirect) {
                $target = $redirect->getTargetPath();
                if ($target !== $path) { $redirect->registerHit(); $this->redirects->save($redirect); $event->setResponse(new RedirectResponse($target, $redirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND)); }
                return;
            }

            $slug = null;
            if (preg_match('#^/strona/([a-z0-9-]+)$#', $path, $match)) $slug = $match[1];
            elseif (in_array($path, ['/content.php', '/index.php'], true)) $slug = trim((string) $request->query->get('url'), '/');
            if ($slug && preg_match('/^[a-z0-9-]+$/', $slug) && ($page = $this->pages->findPublishedBySlug($slug)) && !$page->isAdminOnly()) {
                $target = $page->isHomePage() ? '/' : '/'.$page->getSlug();
                $event->setResponse(new RedirectResponse($target, Response::HTTP_MOVED_PERMANENTLY));
            }
        } catch (\Throwable) {
        }
    }
}
