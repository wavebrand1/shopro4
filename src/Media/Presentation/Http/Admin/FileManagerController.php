<?php
declare(strict_types=1);

namespace App\Media\Presentation\Http\Admin;

use App\Language\Application\SystemTranslator;
use App\Media\Application\AdminFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/files', name: 'admin_file_manager_')]
#[IsGranted('ROLE_EDITOR')]
final class FileManagerController extends AbstractController
{
    public function __construct(private readonly AdminFileManager $files, private readonly SystemTranslator $translator) {}

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $path = (string) ($request->isMethod('POST') ? $request->request->get('path', '') : $request->query->get('path', ''));
        $picker = $request->isMethod('POST') ? $request->request->getBoolean('picker') : $request->query->getBoolean('picker');
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('file-manager', (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
            try {
                $action = (string) $request->request->get('action');
                if ($action === 'mkdir') $this->files->createDirectory($path, (string) $request->request->get('name'));
                elseif ($action === 'touch') $this->files->createFile($path, (string) $request->request->get('name'));
                elseif ($action === 'rename') $this->files->rename((string) $request->request->get('item'), (string) $request->request->get('name'));
                elseif ($action === 'delete') $this->files->delete((string) $request->request->get('item'));
                elseif ($action === 'upload') {
                    $result = $this->files->upload($path, array_values(array_filter($request->files->all('files'), static fn ($file): bool => $file instanceof UploadedFile)));
                    if ($result->uploaded > 0) $this->addFlash('success', sprintf($this->translator->translate('media.uploaded'), $result->uploaded));
                    if ($result->rejected() > 0) {
                        $this->addFlash('error', sprintf($this->translator->translate('media.rejected'), $result->rejected()));
                        foreach ($result->rejections as $reason => $count) $this->addFlash('error', sprintf($this->translator->translate('media.reject_'.$reason), $count));
                    }
                    return $this->redirectToRoute('admin_file_manager_index', ['path' => $path, 'picker' => $picker ? 1 : null]);
                }
                $this->addFlash('success', $this->translator->translate('media.operation_done'));
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->addFlash('error', $this->translator->translate($exception->getMessage()));
            }

            return $this->redirectToRoute('admin_file_manager_index', ['path' => $path, 'picker' => $picker ? 1 : null]);
        }

        try {
            $listing = $this->files->listing($path);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException($this->translator->translate('media.invalid_path'));
        }

        return $this->render('admin/file_manager/index.html.twig', ['listing' => $listing, 'picker' => $picker]);
    }
}
