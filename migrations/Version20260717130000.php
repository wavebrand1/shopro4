<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717130000 extends AbstractMigration
{
    public function getDescription(): string { return 'Adds the complete set of Shopro Legacy system email templates'; }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE system_settings SET configuration = JSON_SET(configuration, '$.site_logo', '/branding/shopro-logo.svg', '$.favicon', '/branding/favicon.svg') WHERE id = 1");
        $button = static fn (string $label, string $url): string => '<p style="margin:28px 0"><a href="'.$url.'" style="display:inline-block;padding:13px 22px;border-radius:8px;background:#5d87ff;color:#fff;text-decoration:none;font-weight:700">'.$label.'</a></p>';
        $templates = [
            ['user_activate_account','Aktywacja konta','Aktywuj konto w [SITE_NAME]','<h1>Witaj, [NAME]!</h1><p>Twoje konto jest już prawie gotowe. Potwierdź adres e-mail, aby bezpiecznie rozpocząć korzystanie z serwisu.</p>'.$button('Aktywuj konto','[LINK]').'<p>Jeżeli to nie Ty zakładałeś konto, zignoruj tę wiadomość.</p>'],
            ['user_password_reminder','Resetowanie hasła','Ustaw nowe hasło w [SITE_NAME]','<h1>Resetowanie hasła</h1><p>Otrzymaliśmy prośbę o zmianę hasła dla konta <strong>[USERNAME]</strong>.</p>'.$button('Ustaw nowe hasło','[LINK]').'<p>Link jest jednorazowy. Jeśli nie prosiłeś o zmianę hasła, nie musisz nic robić.</p>'],
            ['user_admin_create_your_account','Konto utworzone przez administratora','Utworzono Twoje konto w [SITE_NAME]','<h1>Twoje konto jest gotowe</h1><p>Administrator utworzył dla Ciebie konto.</p><p><strong>Login:</strong> [USERNAME]<br><strong>Hasło tymczasowe:</strong> [PASSWORD]</p>'.$button('Zaloguj się','[LINK]').'<p>Po zalogowaniu ustaw własne, unikalne hasło.</p>'],
            ['user_admin_activate_your_account','Konto aktywowane przez administratora','Twoje konto w [SITE_NAME] zostało aktywowane','<h1>Konto aktywne</h1><p>Administrator aktywował konto <strong>[USERNAME]</strong>. Możesz już korzystać ze wszystkich przyznanych funkcji.</p>'.$button('Przejdź do serwisu','[LINK]')],
            ['user_contact_message','Potwierdzenie wiadomości kontaktowej','Otrzymaliśmy Twoją wiadomość','<h1>Dziękujemy za kontakt</h1><p>Wiadomość została bezpiecznie przekazana do naszego zespołu. Odpowiemy możliwie szybko.</p><blockquote style="margin:24px 0;padding:16px;border-left:4px solid #5d87ff;background:#f6f8fc">[MESSAGE]</blockquote>'],
            ['user_message','Wiadomość do użytkownika','[MAILSUBJECT]','<h1>Nowa wiadomość</h1><p>Cześć [NAME],</p><div>[MESSAGE]</div><p style="margin-top:24px">Nadawca: <strong>[SENDER]</strong></p>'],
            ['user_thans_for_registration','Podziękowanie za rejestrację','Dziękujemy za rejestrację w [SITE_NAME]','<h1>Witamy w [SITE_NAME]</h1><p>Dziękujemy za utworzenie konta. Twój login to <strong>[USERNAME]</strong>.</p>'.$button('Otwórz serwis','[URL]')],
            ['user_thans_for_newsletter','Zapis do newslettera','Potwierdzenie zapisu do newslettera [SITE_NAME]','<h1>Dziękujemy za zapis!</h1><p>Adres <strong>[EMAIL]</strong> został dodany do newslettera. Od teraz najważniejsze informacje trafią bezpośrednio do Ciebie.</p>'],
            ['admin_accept_new_user','Akceptacja nowego użytkownika','Użytkownik oczekuje na akceptację','<h1>Nowe konto do akceptacji</h1><p><strong>Użytkownik:</strong> [USERNAME]<br><strong>E-mail:</strong> [EMAIL]<br><strong>IP:</strong> [IP]</p>'.$button('Przejdź do panelu','[LINK]')],
            ['admin_new_rate','Nowa ocena','Nowa ocena w [SITE_NAME]','<h1>Dodano nową ocenę</h1><p>Użytkownik <strong>[NAME]</strong> dodał ocenę: <strong>[RATE]</strong>.</p>'.$button('Zobacz szczegóły','[LINK]')],
            ['admin_alert','Alert systemowy','Alert systemowy [SITE_NAME]','<h1>Alert systemowy</h1><p>[MESSAGE]</p><p><strong>Adres IP:</strong> [IP]</p>'],
            ['admin_contact_message','Wiadomość z formularza kontaktowego','Nowa wiadomość kontaktowa od [NAME]','<h1>Nowa wiadomość</h1><p><strong>Od:</strong> [NAME] ([EMAIL])<br><strong>Telefon:</strong> [PHONE]</p><blockquote style="margin:24px 0;padding:16px;border-left:4px solid #5d87ff;background:#f6f8fc">[MESSAGE]</blockquote>'],
            ['admin_new_user','Nowy użytkownik','Nowe konto w [SITE_NAME]','<h1>Zarejestrowano użytkownika</h1><p><strong>Login:</strong> [USERNAME]<br><strong>E-mail:</strong> [EMAIL]<br><strong>IP:</strong> [IP]</p>'.$button('Otwórz panel','[LINK]')],
        ];
        foreach ($templates as [$code,$name,$subject,$content]) {
            $this->addSql('INSERT IGNORE INTO email_template (code,name,subject,content,`system`) VALUES (:code,:name,:subject,:content,1)', compact('code','name','subject','content'));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE system_settings SET configuration = JSON_REMOVE(configuration, '$.site_logo', '$.favicon') WHERE id = 1");
        $codes = ['user_activate_account','user_password_reminder','user_admin_create_your_account','user_admin_activate_your_account','user_contact_message','user_message','user_thans_for_registration','user_thans_for_newsletter','admin_accept_new_user','admin_new_rate','admin_alert','admin_contact_message','admin_new_user'];
        $this->addSql('DELETE FROM email_template WHERE code IN (?)', [$codes], [\Doctrine\DBAL\ArrayParameterType::STRING]);
    }
}
