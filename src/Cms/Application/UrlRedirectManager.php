<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\UrlRedirect;
use App\Cms\Infrastructure\Persistence\Doctrine\UrlRedirectRepository;

final class UrlRedirectManager
{
    public function __construct(private readonly UrlRedirectRepository $repository) {}

    public function prepare(UrlRedirect $redirect): void
    {
        $source = $redirect->getSourcePath();
        $target = $redirect->getTargetPath();
        $visited = [$source => true];

        for ($depth = 0; $depth < 25; ++$depth) {
            $targetPath = parse_url($target, PHP_URL_PATH);
            if (!is_string($targetPath) || isset($visited[$targetPath])) throw new \LogicException('redirect.loop');
            $visited[$targetPath] = true;
            $next = $this->repository->findActiveExcept($targetPath, $redirect->getId());
            if (!$next) { $redirect->setTargetPath($target); return; }
            $target = $next->getTargetPath();
        }

        throw new \LogicException('redirect.chain_too_long');
    }

    public function registerSlugChange(string $oldSlug, string $newSlug, bool $homePage): void
    {
        $oldPath = '/'.trim($oldSlug, '/');
        $newPath = $homePage ? '/' : '/'.trim($newSlug, '/');
        if ($oldPath === '/' || $oldPath === $newPath) return;

        foreach ($this->repository->findAll() as $existing) {
            if ($existing->getSourcePath() === $oldPath) continue;
            $path = parse_url($existing->getTargetPath(), PHP_URL_PATH);
            if ($path === $oldPath) $existing->setTargetPath($newPath);
        }

        $redirect = $this->repository->findOneBy(['sourcePath' => $oldPath]) ?? new UrlRedirect();
        $redirect->setSourcePath($oldPath); $redirect->setTargetPath($newPath); $redirect->setPermanent(true); $redirect->setActive(true);
        $this->prepare($redirect);
        $this->repository->saveAll([$redirect]);
    }
}
