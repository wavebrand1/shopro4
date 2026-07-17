<?php
declare(strict_types=1);
namespace App\Newsletter\Application\Message;
final readonly class SendNewsletterDelivery { public function __construct(public int $deliveryId) {} }
