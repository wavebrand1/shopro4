<?php

declare(strict_types=1);

namespace App\Module\Presentation\Http\Admin;

use App\Module\Application\ModuleRegistry;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/modules')]
final class ModuleController extends AbstractController
{
    #[Route('', name: 'admin_module_index', methods: ['GET'])]
    public function index(ModuleRegistry $registry, InstalledModuleRepository $repository): Response
    {
        $states = $repository->indexed();
        $modules = [];
        foreach ($registry->all() as $definition) {
            $modules[] = ['definition' => $definition, 'state' => $states[$definition->code()] ?? null];
        }
        return $this->render('admin/module/index.html.twig', ['modules' => $modules]);
    }
}
