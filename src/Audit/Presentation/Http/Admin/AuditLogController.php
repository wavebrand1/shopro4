<?php
declare(strict_types=1);

namespace App\Audit\Presentation\Http\Admin;

use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs', name: 'admin_audit_log_')]
#[IsGranted('ROLE_ADMIN')]
final class AuditLogController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AuditLogRepository $logs): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 25);
        if (!in_array($limit, [10, 25, 50, 100], true)) $limit = 25;
        $result = $logs->filtered($request->query->getString('from') ?: null, $request->query->getString('to') ?: null, $request->query->getString('type') ?: null, $page, $limit);

        return $this->render('admin/audit_log/index.html.twig', [
            'logs' => $result['items'], 'total' => $result['total'], 'page' => $page, 'limit' => $limit,
            'pages' => max(1, (int) ceil($result['total'] / $limit)),
            'filters' => ['from' => $request->query->getString('from'), 'to' => $request->query->getString('to'), 'type' => $request->query->getString('type')],
        ]);
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(Request $request, AuditLogRepository $logs): Response
    {
        if (!$this->isCsrfTokenValid('clear-audit-log', (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
        $logs->clear();
        $this->addFlash('success', $this->translator->translate('logs.cleared'));

        return $this->redirectToRoute('admin_audit_log_index');
    }
}
