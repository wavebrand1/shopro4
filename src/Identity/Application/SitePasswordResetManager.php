<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\SitePasswordResetToken;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\SitePasswordResetTokenRepository;

final class SitePasswordResetManager
{
    public function __construct(private readonly SitePasswordResetTokenRepository $tokens) {}
    public function create(SiteUser $user): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $this->tokens->replaceForUser($user, new SitePasswordResetToken($user, hash('sha256', $rawToken), new \DateTimeImmutable('+1 hour')));
        return $rawToken;
    }
    public function findUsable(string $rawToken): ?SitePasswordResetToken { return $this->tokens->findUsable($rawToken); }
    public function consume(SitePasswordResetToken $token): void { $token->markUsed(); $this->tokens->save($token); }
}
