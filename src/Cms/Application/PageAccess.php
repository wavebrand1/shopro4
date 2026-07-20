<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\Page;
use App\Identity\Domain\Entity\SiteUser;

final class PageAccess
{
    public function isAllowed(Page $page, ?SiteUser $user): bool
    {
        if ('Public' === $page->getAccess()) return true;
        if (!$user || !$user->isActive()) return false;
        if ('Registered' === $page->getAccess()) return true;
        if ('Membership' !== $page->getAccess()) return false;

        foreach ($page->getMemberships() as $membership) {
            if ($user->hasActiveMembership($membership)) return true;
        }

        return false;
    }
}
