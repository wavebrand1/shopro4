<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Api;

use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[\App\Module\Application\RequiresModule('identity')]
final class MeController extends AbstractController
{
    #[Route('/api/v1/me', name: 'api_v1_me', methods: ['GET'])]
    public function __invoke(Request $request, AdminUserRepository $users): JsonResponse
    {
        $authorization = (string) $request->headers->get('Authorization');
        if (!str_starts_with($authorization, 'Bearer ')) return $this->json(['error' => 'missing_token'], 401);
        $token = substr($authorization, 7);
        if (!str_starts_with($token, 'shp4_') || strlen($token) !== 69) return $this->json(['error' => 'invalid_token'], 401);
        $user = $users->findOneBy(['apiTokenHash' => hash('sha256', $token), 'apiEnabled' => true, 'active' => true]);
        if (!$user) return $this->json(['error' => 'invalid_token'], 401);
        if (!$user->hasApiScope('read')) return $this->json(['error' => 'insufficient_scope', 'required_scope' => 'read'], 403);
        return $this->json(['id' => $user->getId(), 'username' => $user->getUsername(), 'email' => $user->getEmail(), 'scopes' => $user->getApiScopes()]);
    }
}
