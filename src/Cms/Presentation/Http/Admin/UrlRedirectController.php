<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\UrlRedirect;
use App\Cms\Application\UrlRedirectManager;
use App\Cms\Infrastructure\Persistence\Doctrine\UrlRedirectRepository;
use App\Cms\Presentation\Form\UrlRedirectType;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/configuration/redirects')]
final class UrlRedirectController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}
    #[Route('', name: 'admin_url_redirect_index', methods: ['GET'])]
    public function index(UrlRedirectRepository $repository): Response { return $this->render('admin/redirect/index.html.twig', ['redirects' => $repository->findBy([], ['sourcePath' => 'ASC'])]); }
    #[Route('/new', name: 'admin_url_redirect_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UrlRedirectRepository $repository, UrlRedirectManager $manager): Response { return $this->form($request, new UrlRedirect(), $repository, $manager); }
    #[Route('/{id}/edit', name: 'admin_url_redirect_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(UrlRedirect $redirect, Request $request, UrlRedirectRepository $repository, UrlRedirectManager $manager): Response { return $this->form($request, $redirect, $repository, $manager); }
    #[Route('/{id}/delete', name: 'admin_url_redirect_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(UrlRedirect $redirect, Request $request, UrlRedirectRepository $repository): Response
    {
        if ($this->isCsrfTokenValid('delete-redirect-'.$redirect->getId(), (string) $request->request->get('_token'))) { $repository->remove($redirect); $this->addFlash('success', $this->translator->translate('redirect.deleted')); }
        return $this->redirectToRoute('admin_url_redirect_index');
    }
    private function form(Request $request, UrlRedirect $redirect, UrlRedirectRepository $repository, UrlRedirectManager $manager): Response
    {
        $form = $this->createForm(UrlRedirectType::class, $redirect); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try { $manager->prepare($redirect); $repository->save($redirect); $this->addFlash('success', $this->translator->translate('redirect.saved')); return $this->redirectToRoute('admin_url_redirect_index'); }
            catch (\LogicException $exception) { $form->addError(new \Symfony\Component\Form\FormError($this->translator->translate($exception->getMessage()))); }
        }
        return $this->render('admin/redirect/form.html.twig', ['form' => $form, 'redirect' => $redirect]);
    }
}
