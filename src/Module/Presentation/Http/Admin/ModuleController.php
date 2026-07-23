<?php

declare(strict_types=1);

namespace App\Module\Presentation\Http\Admin;

use App\Module\Application\ModuleRegistry;
use App\Module\Application\ModuleLifecycleException;
use App\Module\Application\ModuleLifecycleManager;
use App\Module\Application\ModuleAvailability;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/modules')]
final class ModuleController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_module_index', methods: ['GET'])]
    public function index(ModuleRegistry $registry, InstalledModuleRepository $repository, ModuleAvailability $runtime): Response
    {
        $states = $repository->indexed();
        $modules = [];
        foreach ($registry->all() as $definition) {
            $modules[] = ['definition' => $definition, 'state' => $states[$definition->code()] ?? null, 'runtimeEnabled' => $runtime->isEnabled($definition->code())];
        }
        return $this->render('admin/module/index.html.twig', [
            'modules' => $modules,
            'orphaned' => array_diff_key($states, $registry->all()),
        ]);
    }

    #[Route('/{code}/enable', name: 'admin_module_enable', requirements: ['code' => '[a-z][a-z0-9_-]{1,79}'], methods: ['POST'])]
    public function enable(string $code, Request $request, ModuleLifecycleManager $manager): Response
    {
        return $this->changeState($code, true, $request, $manager);
    }

    #[Route('/{code}/disable', name: 'admin_module_disable', requirements: ['code' => '[a-z][a-z0-9_-]{1,79}'], methods: ['POST'])]
    public function disable(string $code, Request $request, ModuleLifecycleManager $manager): Response
    {
        return $this->changeState($code, false, $request, $manager);
    }

    private function changeState(string $code, bool $enable, Request $request, ModuleLifecycleManager $manager): Response
    {
        if (!$this->isCsrfTokenValid('module-state-'.$code, (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
        $request->attributes->set('_shopro_module_outcome', 'applied');
        try {
            $enable ? $manager->enable($code) : $manager->disable($code);
            $this->addFlash('success', $this->translator->translate($enable ? 'module.enabled_success' : 'module.disabled_success'));
        } catch (ModuleLifecycleException $exception) {
            $request->attributes->set('_shopro_module_outcome', 'denied');
            $request->attributes->set('_shopro_module_reason', $exception->reason);
            $this->addFlash('error', $this->translator->translate($exception->reason));
        }

        return $this->redirectToRoute('admin_module_index');
    }
}
