<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Module\Application\ModuleIntegrityChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(private readonly ModuleIntegrityChecker $integrity) {}

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $response = new JsonResponse([
            'application' => 'shopro4',
            'status' => 'ok',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    #[Route('/health/ready', name: 'app_health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        try {
            $report = $this->integrity->check();
            $healthy = $report->isHealthy();
            $data = [
                'application' => 'shopro4',
                'status' => $healthy ? 'ready' : 'unavailable',
                'modules' => [
                    'status' => $healthy ? 'ok' : 'error',
                    'invalid' => array_values(array_unique(array_column($report->issues, 'code'))),
                    'orphaned' => count($report->orphaned),
                ],
            ];
        } catch (\Throwable) {
            $healthy = false;
            $data = [
                'application' => 'shopro4',
                'status' => 'unavailable',
                'modules' => ['status' => 'unknown', 'invalid' => [], 'orphaned' => 0],
            ];
        }

        $response = new JsonResponse($data, $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
