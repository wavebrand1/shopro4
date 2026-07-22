<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Admin;

use App\Identity\Domain\Entity\Membership;
use App\Identity\Infrastructure\Persistence\Doctrine\MembershipRepository;
use App\Identity\Presentation\Form\MembershipType;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/memberships')]
#[\App\Module\Application\RequiresModule('identity')]
final class MembershipController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}
    #[Route('', name: 'admin_membership_index', methods: ['GET'])]
    public function index(MembershipRepository $repository): Response
    {
        return $this->render('admin/membership/index.html.twig', ['memberships' => $repository->findBy([], ['title' => 'ASC'])]);
    }
    #[Route('/new', name: 'admin_membership_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MembershipRepository $repository): Response { return $this->form($request, new Membership(), $repository); }
    #[Route('/{id}/edit', name: 'admin_membership_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Membership $membership, Request $request, MembershipRepository $repository): Response { return $this->form($request, $membership, $repository); }
    private function form(Request $request, Membership $membership, MembershipRepository $repository): Response
    {
        $form = $this->createForm(MembershipType::class, $membership);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($membership);
            $this->addFlash('success', $this->translator->translate('membership.saved'));
            return $this->redirectToRoute('admin_membership_index');
        }
        return $this->render('admin/membership/form.html.twig', ['form' => $form, 'membership' => $membership]);
    }
}
