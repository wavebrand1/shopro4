<?php

declare(strict_types=1);

namespace App\Newsletter\Presentation\Http;

use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Newsletter\Application\UnsubscribeToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UnsubscribeController extends AbstractController
{
    #[Route('/newsletter/unsubscribe', name: 'newsletter_unsubscribe', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, UnsubscribeToken $tokens, AdminUserRepository $users): Response
    {
        $email = $tokens->verify((string) ($request->query->get('token') ?: $request->request->get('token')));
        if (!$email) return new Response('Link wypisu jest nieprawidłowy lub wygasł.', 400);
        $user = $users->findOneBy(['email' => $email]);
        if ($user) { $user->setNewsletter(false); $users->save($user); }
        return new Response('<!doctype html><html lang="pl"><meta charset="utf-8"><title>Wypisano</title><body><main><h1>Adres został wypisany</h1><p>Nie będziemy wysyłać kolejnych wiadomości newslettera.</p></main></body></html>');
    }
}
