<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Admin;

use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Identity\Presentation\Form\AdminUserType;
use App\Language\Application\SystemTranslator;
use App\Settings\Application\SettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
#[\App\Module\Application\RequiresModule('identity')]
final class UserController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, AdminUserRepository $users, SettingsProvider $settings): Response
    {
        $query = trim((string) $request->query->get('q'));
        $qb = $users->createQueryBuilder('u')->orderBy('u.id', 'DESC');
        if ($query !== '') $qb->andWhere('u.username LIKE :q OR u.email LIKE :q OR u.firstName LIKE :q OR u.lastName LIKE :q')->setParameter('q', '%'.$query.'%');
        $page = max(1, $request->query->getInt('page', 1)); $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $total = (int) (clone $qb)->select('COUNT(u.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        return $this->render('admin/user/index.html.twig', ['users' => $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult(), 'query' => $query, 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $limit)), 'total' => $total]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AdminUserRepository $users, UserPasswordHasherInterface $hasher): Response
    {
        return $this->form($request, new AdminUser('', ''), $users, $hasher);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(AdminUser $user, Request $request, AdminUserRepository $users, UserPasswordHasherInterface $hasher): Response
    {
        return $this->form($request, $user, $users, $hasher);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(AdminUser $user, Request $request, AdminUserRepository $users): Response
    {
        if ($user === $this->getUser()) $this->addFlash('error', $this->translator->translate('users.cannot_delete_self'));
        elseif ($this->isCsrfTokenValid('delete-user-'.$user->getId(), (string) $request->request->get('_token'))) { $users->remove($user); $this->addFlash('success', $this->translator->translate('users.deleted')); }
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/api-token', name: 'admin_user_api_token', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function apiToken(AdminUser $user, Request $request, AdminUserRepository $users): Response
    {
        if (!$this->isCsrfTokenValid('api-token-'.$user->getId(), (string) $request->request->get('_token'))) return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
        if ($request->request->get('action') === 'revoke') {
            $user->revokeApiToken();
            $this->addFlash('success', $this->translator->translate('users.token_revoked'));
        } else {
            $token = $user->rotateApiToken();
            $this->addFlash('api_token', $token);
            $this->addFlash('success', $this->translator->translate('users.token_generated'));
        }
        $users->save($user);
        return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
    }

    private function form(Request $request, AdminUser $user, AdminUserRepository $users, UserPasswordHasherInterface $hasher): Response
    {
        $wasActive = $user->isActive();
        $wasAdministrator = $user->isAdministrator();
        $form = $this->createForm(AdminUserType::class, $user); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($wasActive && !$user->isActive()) {
                $currentUser = $this->getUser();
                $message = $currentUser instanceof AdminUser && $currentUser->getId() === $user->getId()
                    ? 'users.cannot_deactivate_self'
                    : ($user->isAdministrator() && $users->countActiveAdministrators() <= 1 ? 'users.cannot_deactivate_last' : null);
                if ($message !== null) {
                    $user->setActive(true);
                    $form->get('active')->addError(new FormError($this->translator->translate($message)));
                }
            }
            if ($wasAdministrator && !$user->isAdministrator()) {
                $currentUser = $this->getUser();
                $message = $currentUser instanceof AdminUser && $currentUser->getId() === $user->getId()
                    ? 'users.cannot_demote_self'
                    : ($users->countActiveAdministrators() <= 1 ? 'users.cannot_demote_last' : null);
                if ($message !== null) {
                    $user->setAssignedRoles(['ROLE_ADMIN']);
                    $form->get('assignedRoles')->addError(new FormError($this->translator->translate($message)));
                }
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $password = (string) $form->get('plainPassword')->getData();
            if ($password !== '') $user->setPassword($hasher->hashPassword($user, $password));
            $users->save($user); $this->addFlash('success', $this->translator->translate('users.saved'));
            return $this->redirectToRoute('admin_user_index');
        }
        return $this->render('admin/user/form.html.twig', ['form' => $form, 'user' => $user]);
    }
}
