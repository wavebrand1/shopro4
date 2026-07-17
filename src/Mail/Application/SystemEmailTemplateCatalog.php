<?php
declare(strict_types=1);
namespace App\Mail\Application;

final class SystemEmailTemplateCatalog
{
    /** @return array<string, array{label:string, description:string}> */
    public function all(): array
    {
        return [
            'user_activate_account'=>['label'=>'Użytkownik — aktywacja konta','description'=>'Po rejestracji, gdy użytkownik musi potwierdzić adres e-mail. Zawiera link aktywacyjny.'],
            'user_password_reminder'=>['label'=>'Użytkownik — resetowanie hasła','description'=>'Po zgłoszeniu prośby o zmianę hasła. Zawiera jednorazowy link do ustawienia nowego hasła.'],
            'user_admin_create_your_account'=>['label'=>'Użytkownik — konto utworzone przez administratora','description'=>'Gdy administrator tworzy konto w panelu. Przekazuje login, hasło tymczasowe i link logowania.'],
            'user_admin_activate_your_account'=>['label'=>'Użytkownik — konto aktywowane przez administratora','description'=>'Gdy administrator ręcznie aktywuje konto użytkownika. Informuje o uzyskaniu dostępu.'],
            'user_contact_message'=>['label'=>'Użytkownik — potwierdzenie formularza kontaktowego','description'=>'Potwierdzenie dla nadawcy po poprawnym wysłaniu formularza kontaktowego.'],
            'user_message'=>['label'=>'Użytkownik — wiadomość indywidualna','description'=>'Uniwersalna wiadomość do konkretnego użytkownika wysyłana przez administratora lub funkcję systemową.'],
            'user_thans_for_registration'=>['label'=>'Użytkownik — podziękowanie za rejestrację','description'=>'Wiadomość powitalna po zakończeniu rejestracji. Kod zachowuje historyczną pisownię ze starego Shopro.'],
            'user_thans_for_newsletter'=>['label'=>'Użytkownik — potwierdzenie newslettera','description'=>'Potwierdzenie wysyłane osobie, która zapisała swój adres do newslettera.'],
            'admin_accept_new_user'=>['label'=>'Administrator — konto oczekujące na akceptację','description'=>'Powiadomienie o nowym koncie wymagającym ręcznej weryfikacji przez administratora.'],
            'admin_new_rate'=>['label'=>'Administrator — nowa ocena','description'=>'Gdy użytkownik doda ocenę lub recenzję w module obsługującym oceny.'],
            'admin_alert'=>['label'=>'Administrator — alert systemowy','description'=>'Przy istotnym zdarzeniu bezpieczeństwa albo błędzie wymagającym reakcji administratora.'],
            'admin_contact_message'=>['label'=>'Administrator — formularz kontaktowy','description'=>'Przekazuje administratorowi pełną treść nowej wiadomości z formularza kontaktowego.'],
            'admin_new_user'=>['label'=>'Administrator — nowy użytkownik','description'=>'Informuje administratora o utworzeniu nowego konta użytkownika.'],
            'account_activation'=>['label'=>'Zgodność — aktywacja konta (stary kod)','description'=>'Kod zgodności z wcześniejszą wersją Shopro 4.0. Nowe funkcje używają user_activate_account.'],
            'security_alert'=>['label'=>'Zgodność — alert bezpieczeństwa (stary kod)','description'=>'Kod zgodności z wcześniejszą wersją Shopro 4.0. Nowe funkcje używają admin_alert.'],
        ];
    }
    /** @return array<string,string> */
    public function choices(): array { $result=[]; foreach($this->all() as $code=>$item) $result[$item['label']]=$code; return $result; }
    public function description(string $code): string { return $this->all()[$code]['description'] ?? ''; }
}
