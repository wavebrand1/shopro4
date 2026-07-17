<?php

declare(strict_types=1);

namespace App\Mail\Application;

use App\Settings\Application\SettingsProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class EmailLayoutRenderer
{
    public function __construct(private readonly SettingsProvider $settings, #[Autowire(service: 'html_sanitizer.sanitizer.app.email_content')] private readonly HtmlSanitizerInterface $sanitizer) {}

    public function render(string $content, ?string $preheader = null): string
    {
        $siteName = htmlspecialchars((string) $this->settings->get('site_name', 'Shopro'), ENT_QUOTES);
        $siteUrl = htmlspecialchars(rtrim((string) $this->settings->get('site_url'), '/'), ENT_QUOTES);
        $logo = (string) $this->settings->get('site_logo');
        $logoHtml = $logo !== '' ? '<img src="'.htmlspecialchars(rtrim((string) $this->settings->get('site_url'), '/').$logo, ENT_QUOTES).'" alt="'.$siteName.'" style="display:block;max-width:220px;max-height:64px;width:auto;height:auto;border:0">' : '<span style="color:#17243d;font-size:22px;font-weight:700">'.$siteName.'</span>';
        $accent = match ((string) $this->settings->get('theme_variant', 'blue')) { 'violet' => '#7c5cff', 'emerald' => '#12a77a', 'orange' => '#f27a3d', default => '#5d87ff' };
        $safeContent = $this->sanitizer->sanitize($content);
        $preheaderText = htmlspecialchars($preheader ?? '', ENT_QUOTES);

        return '<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="x-apple-disable-message-reformatting"><title>'.$siteName.'</title><style>@media only screen and (max-width:620px){.email-shell{width:100%!important}.email-pad{padding:28px 20px!important}.email-footer{padding:20px!important}}</style></head><body style="margin:0;padding:0;background:#f3f6fb;color:#1d293d;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%"><div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">'.$preheaderText.'</div><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f3f6fb"><tr><td align="center" style="padding:28px 12px"><table role="presentation" class="email-shell" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background:#fff;border:1px solid #e4e9f2;border-radius:16px;overflow:hidden"><tr><td style="height:5px;background:'.$accent.';font-size:0;line-height:0">&nbsp;</td></tr><tr><td style="padding:24px 36px;border-bottom:1px solid #edf0f5"><a href="'.$siteUrl.'" style="text-decoration:none">'.$logoHtml.'</a></td></tr><tr><td class="email-pad" style="padding:38px 36px;font-size:16px;line-height:1.65;color:#344054">'.$safeContent.'</td></tr><tr><td class="email-footer" style="padding:22px 36px;background:#f8fafc;border-top:1px solid #edf0f5;color:#7b879b;font-size:12px;line-height:1.5">Wiadomość wysłana przez '.$siteName.'.</td></tr></table></td></tr></table></body></html>';
    }
}
