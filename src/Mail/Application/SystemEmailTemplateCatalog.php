<?php
declare(strict_types=1);

namespace App\Mail\Application;

use App\Language\Application\SystemTranslator;

final class SystemEmailTemplateCatalog
{
    private const CODES = [
        'user_activate_account', 'user_password_reminder', 'user_admin_create_your_account',
        'user_admin_activate_your_account', 'user_contact_message', 'user_message',
        'user_thans_for_registration', 'user_thans_for_newsletter', 'admin_accept_new_user',
        'admin_new_rate', 'admin_alert', 'admin_contact_message', 'admin_new_user',
        'account_activation', 'security_alert',
    ];

    public function __construct(private readonly SystemTranslator $translator) {}

    /** @return list<string> */
    public function codes(): array
    {
        return self::CODES;
    }

    /** @return array<string, array{label:string, description:string}> */
    public function all(): array
    {
        $result = [];
        foreach (self::CODES as $code) {
            $result[$code] = [
                'label' => $this->translator->translate('email_template.code.'.$code),
                'description' => $this->translator->translate('email_template.description.'.$code),
            ];
        }

        return $result;
    }

    /** @return array<string,string> */
    public function choices(): array
    {
        $result = [];
        foreach ($this->all() as $code => $item) {
            $result[$item['label']] = $code;
        }

        return $result;
    }

    public function description(string $code): string
    {
        return $this->all()[$code]['description'] ?? '';
    }
}
