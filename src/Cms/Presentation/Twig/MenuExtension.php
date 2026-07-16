<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Twig;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuItemRepository $items,
        private readonly UrlGeneratorInterface $urls,
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

            $branch[] = [
                'id' => $id,
                'name' => $item->getName(),
                'caption' => $item->getCaption(),
                'url' => $this->resolveUrl($item),
                'target' => $item->getTarget(),
                'children' => $this->branch($items, $id, [...$visited, $id]),
            ];
        }

        return $branch;
    }

    private function resolveUrl(MenuItem $item): ?string
    {
        if ($item->isHomePage()) {
            return $this->urls->generate('app_home');
        }

        return match ($item->getContentType()) {
            MenuItem::TYPE_PAGE => $item->getPage() !== null
                ? $this->urls->generate('cms_page_show', ['slug' => $item->getPage()->getSlug()])
                : null,
            MenuItem::TYPE_WEB => $item->getLink(),
            default => null,
        };
    }
}
