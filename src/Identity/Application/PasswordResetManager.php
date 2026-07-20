<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\PasswordResetToken;
use App\Identity\Infrastructure\Persistence\Doctrine\PasswordResetTokenRepository;

final class PasswordResetManager
{
    public function __construct(private readonly PasswordResetTokenRepository $tokens) {}

    public function create(AdminUser $user): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $this->tokens->replaceForUser($user, new PasswordResetToken(
            $user,
            hash('sha256', $rawToken),
            new \DateTimeImmutable('+1 hour'),
        ));
        return $rawToken;
    }

    public function findUsable(string $rawToken): ?PasswordResetToken { return $this->tokens->findUsable($rawToken); }
    public function consume(PasswordResetToken $token): void { $token->markUsed(); $this->tokens->save($token); }
}
