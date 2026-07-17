<?php
declare(strict_types=1);
namespace App\Mail\Infrastructure\Persistence\Doctrine;
use App\Mail\Domain\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, EmailTemplate::class); }
    public function save(EmailTemplate $template): void { $this->getEntityManager()->persist($template); $this->getEntityManager()->flush(); }

    /** @return array<string, int> */
    public function choices(): array
    {
        $choices = ['Domyślny szablon systemowy' => 0];
        try {
            foreach ($this->findBy([], ['name' => 'ASC']) as $template) $choices[$template->getName()] = (int) $template->getId();
        } catch (\Throwable) {
            // Keep configuration readable while the deployment migration is still running.
        }
        return $choices;
    }
}
