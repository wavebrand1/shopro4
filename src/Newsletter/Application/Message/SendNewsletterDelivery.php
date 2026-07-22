<?php

declare(strict_types=1);

namespace App\Newsletter\Application\Message;

use App\Module\Application\ModuleAwareMessage;

final readonly class SendNewsletterDelivery implements ModuleAwareMessage
{
    public function __construct(public int $deliveryId) {}

    public function moduleCode(): string { return 'newsletter'; }
}
