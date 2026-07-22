<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Admin;

use App\Identity\Application\SiteRegistrationMailer;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use App\Identity\Presentation\Form\SiteUserType;
use App\Language\Application\SystemTranslator;
use App\Settings\Application\SettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/site-users')]
#[IsGranted('ROLE_ADMIN')]
#[\App\Module\Application\RequiresModule('identity')]
final class SiteUserController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_site_user_index', methods: ['GET'])]
    public function index(Request $request, SiteUserRepository $users, SettingsProvider $settings): Response
    {
        $query = trim((string) $request->query->get('q'));
        $qb = $users->createQueryBuilder('user')->orderBy('user.id', 'DESC');
        if ($query !== '') $qb->andWhere('user.username LIKE :query OR user.email LIKE :query')->setParameter('query', '%'.$query.'%');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $total = (int) (clone $qb)->select('COUNT(user.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        return $this->render('admin/site_user/index.html.twig', ['users' => $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult(), 'query' => $query, 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $limit)), 'total' => $total]);
    }

    #[Route('/new', name: 'admin_site_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SiteUserRepository $users, UserPasswordHasherInterface $hasher, SiteRegistrationMailer $mailer): Response { return $this->form($request, new SiteUser('', ''), $users, $hasher, $mailer); }
    #[Route('/{id}/edit', name: 'admin_site_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(SiteUser $user, Request $request, SiteUserRepository $users, UserPasswordHasherInterface $hasher, SiteRegistrationMailer $mailer): Response { return $this->form($request, $user, $users, $hasher, $mailer); }
    #[Route('/{id}/delete', name: 'admin_site_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(SiteUser $user, Request $request, SiteUserRepository $users): Response
    {
        if ($this->isCsrfTokenValid('delete-site-user-'.$user->getId(), (string) $request->request->get('_token'))) { $users->remove($user); $this->addFlash('success', $this->translator->translate('site_users.deleted')); }
        return $this->redirectToRoute('admin_site_user_index');
    }
    private function form(Request $request, SiteUser $user, SiteUserRepository $users, UserPasswordHasherInterface $hasher, SiteRegistrationMailer $mailer): Response
    {
        $wasActive = $user->isActive(); $existing = $user->getId() !== null;
        $form = $this->createForm(SiteUserType::class, $user); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = (string) $form->get('plainPassword')->getData();
            if ($password !== '') $user->setPassword($hasher->hashPassword($user, $password));
            $users->save($user);
            if ($existing && !$wasActive && $user->isActive()) {
                try { $mailer->sendAdminActivated($user, $this->generateUrl('site_login', [], UrlGeneratorInterface::ABSOLUTE_URL)); } catch (\Throwable) {}
            }
            $this->addFlash('success', $this->translator->translate('site_users.saved'));
            return $this->redirectToRoute('admin_site_user_index');
        }
        return $this->render('admin/site_user/form.html.twig', ['form' => $form, 'user' => $user]);
    }
}
