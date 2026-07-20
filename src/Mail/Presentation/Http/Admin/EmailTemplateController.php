<?php
declare(strict_types=1);

namespace App\Mail\Presentation\Http\Admin;

use App\Language\Application\SystemTranslator;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Mail\Presentation\Form\EmailTemplateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/configuration/email-templates')]
final class EmailTemplateController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_email_template_index', methods: ['GET'])]
    public function index(EmailTemplateRepository $repository): Response
    {
        return $this->render('admin/email_template/index.html.twig', ['templates' => $repository->findBy([], ['name' => 'ASC'])]);
    }

    #[Route('/new', name: 'admin_email_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EmailTemplateRepository $repository): Response
    {
        return $this->form($request, new EmailTemplate(), $repository);
    }

    #[Route('/{id}/edit', name: 'admin_email_template_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(EmailTemplate $template, Request $request, EmailTemplateRepository $repository): Response
    {
        return $this->form($request, $template, $repository);
    }

    private function form(Request $request, EmailTemplate $template, EmailTemplateRepository $repository): Response
    {
        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($template);
            $this->addFlash('success', $this->translator->translate('email_template.saved'));

            return $this->redirectToRoute('admin_email_template_index');
        }

        return $this->render('admin/email_template/form.html.twig', ['form' => $form, 'template' => $template]);
    }
}
