<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Admin;

use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Identity\Presentation\Form\AdminUserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, AdminUserRepository $users): Response
    {
        $query = trim((string) $request->query->get('q'));
        $qb = $users->createQueryBuilder('u')->orderBy('u.id', 'DESC');
        if ($query !== '') $qb->andWhere('u.username LIKE :q OR u.email LIKE :q OR u.firstName LIKE :q OR u.lastName LIKE :q')->setParameter('q', '%'.$query.'%');
        return $this->render('admin/user/index.html.twig', ['users' => $qb->getQuery()->getResult(), 'query' => $query]);
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
        if ($user === $this->getUser()) $this->addFlash('error', 'Nie możesz usunąć aktualnie zalogowanego konta.');
        elseif ($this->isCsrfTokenValid('delete-user-'.$user->getId(), (string) $request->request->get('_token'))) { $users->remove($user); $this->addFlash('success', 'Użytkownik został usunięty.'); }
        return $this->redirectToRoute('admin_user_index');
    }

    private function form(Request $request, AdminUser $user, AdminUserRepository $users, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(AdminUserType::class, $user); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = (string) $form->get('plainPassword')->getData();
            if ($password !== '') $user->setPassword($hasher->hashPassword($user, $password));
            $users->save($user); $this->addFlash('success', 'Użytkownik został zapisany.');
            return $this->redirectToRoute('admin_user_index');
        }
        return $this->render('admin/user/form.html.twig', ['form' => $form, 'user' => $user]);
    }
}
