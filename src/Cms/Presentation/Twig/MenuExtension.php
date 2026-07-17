<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Twig;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Domain\Entity\MenuItemTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Domain\Entity\Language;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MenuExtension extends AbstractExtension
{
    /** @var array<int,MenuItemTranslation> */
    private array $translations=[];

    public function __construct(
        private readonly MenuItemRepository $items,
        private readonly UrlGeneratorInterface $urls,
        private readonly RequestStack $requests,
        private readonly LocalizedPageUrlGenerator $localizedUrls,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('shopro_menu', $this->menu(...))];
    }

    /** @return list<array{id: int, name: string, caption: ?string, url: ?string, target: string, children: array}> */
    public function menu(int $place): array
    {
        $items = $this->items->findActiveByPlace($place);
        $this->translations=[];
        $language=$this->requests->getCurrentRequest()?->attributes->get('_shopro_language');
        if($language instanceof Language&&!$language->isDefaultLanguage()&&$items){
            foreach($this->em->getRepository(MenuItemTranslation::class)->findBy(['language'=>$language,'menuItem'=>$items]) as $translation)$this->translations[(int)$translation->getMenuItem()->getId()]=$translation;
        }

        return $this->branch($items, null, []);
    }

    /**
     * @param list<MenuItem> $items
     * @param list<int> $visited
     * @return list<array{id: int, name: string, caption: ?string, url: ?string, target: string, children: array}>
     */
    private function branch(array $items, ?int $parentId, array $visited): array
    {
        $branch = [];
        foreach ($items as $item) {
            $id = $item->getId();
            if ($id === null || in_array($id, $visited, true) || $item->getParent()?->getId() !== $parentId) {
                continue;
            }

            $translation=$this->translations[$id]??null;
            $branch[] = [
                'id' => $id,
                'name' => $translation?->getName()??$item->getName(),
                'caption' => $translation?->getCaption()??$item->getCaption(),
                'url' => $this->resolveUrl($item,$translation),
                'target' => $item->getTarget(),
                'children' => $this->branch($items, $id, [...$visited, $id]),
            ];
        }

        return $branch;
    }

    private function resolveUrl(MenuItem $item,?MenuItemTranslation $translation=null): ?string
    {
        if ($item->isHomePage()) {
            return $this->urls->generate('app_home');
        }

        return match ($item->getContentType()) {
            MenuItem::TYPE_PAGE => $item->getPage() !== null
                ? $this->pageUrl($item->getPage())
                : null,
            MenuItem::TYPE_WEB => $translation?->getLink()??$item->getLink(),
            default => null,
        };
    }

    private function pageUrl(\App\Cms\Domain\Entity\Page $page): string
    {
        $language=$this->requests->getCurrentRequest()?->attributes->get('_shopro_language');
        return $language instanceof Language?$this->localizedUrls->page($page,$language):$this->urls->generate('cms_page_show',['slug'=>$page->getSlug()]);
    }
}
