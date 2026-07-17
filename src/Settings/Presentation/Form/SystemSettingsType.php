<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Form;

use App\Settings\Application\FrontThemeRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

final class SystemSettingsType extends AbstractType
{
    public function __construct(private readonly FrontThemeRegistry $themes) {}

    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $yesNo = static fn (string $label): array => ['label' => $label, 'choices' => ['Tak' => true, 'Nie' => false], 'expanded' => true, 'multiple' => false];
        $positive = static fn (string $label): array => ['label' => $label, 'constraints' => [new Positive()]];

        $b
            ->add('site_name', TextType::class, ['label' => 'Nazwa witryny'])
            ->add('company', TextType::class, ['label' => 'Nazwa firmy'])
            ->add('site_url', UrlType::class, ['label' => 'Adres witryny', 'constraints' => [new Url(requireTld: false)]])
            ->add('site_dir', TextType::class, ['label' => 'Katalog instalacji', 'help' => 'Pusty dla instalacji w katalogu głównym.', 'required' => false])
            ->add('site_email', EmailType::class, ['label' => 'E-mail witryny', 'constraints' => [new Email()]])
            ->add('theme', ChoiceType::class, ['label' => 'Szablon strony', 'choices' => $this->themes->frontChoices()])
            ->add('theme_variant', ChoiceType::class, ['label' => 'Wariant szablonu', 'choices' => $this->themes->frontVariantChoices()])
            ->add('admin_theme', ChoiceType::class, ['label' => 'Szablon panelu administracyjnego', 'choices' => $this->themes->adminChoices()])
            ->add('admin_theme_variant', ChoiceType::class, ['label' => 'Wariant szablonu PA', 'choices' => ['Domyślny' => 'default']])
            ->add('date_short', ChoiceType::class, ['label' => 'Krótki format daty', 'choices' => ['MM-DD-YYYY' => '%m-%d-%Y', 'D-MM-YYYY' => '%e-%m-%Y', 'MM-D-YY' => '%m-%e-%y', 'D-MMM-YY' => '%e-%m-%y', 'DD Mon YYYY' => '%d %b %Y']])
            ->add('date_long', ChoiceType::class, ['label' => 'Długi format daty', 'choices' => ['Miesiąc DD, YYYY HH:MM AM' => '%B %d, %Y %I:%M %p', 'DD Miesiąc YYYY HH:MM AM' => '%d %B %Y %I:%M %p', 'Miesiąc DD, YYYY' => '%B %d, %Y', 'DD Miesiąc, YYYY' => '%d %B, %Y', 'Dzień DD Miesiąc YYYY' => '%A %d %B %Y', 'Dzień DD Miesiąc YYYY HH:MM' => '%A %d %B %Y %H:%M', 'Dzień DD, Miesiąc' => '%a %d, %B']])
            ->add('time_format', ChoiceType::class, ['label' => 'Format czasu', 'choices' => ['12-godzinny (02:30 PM)' => '%I:%M %p', '12-godzinny (02:30 pm)' => '%I:%M %P', '24-godzinny (14:30)' => '%H:%M', 'Godzina (14)' => '%k']])
            ->add('timezone', ChoiceType::class, ['label' => 'Strefa czasowa', 'choices' => array_flip(Timezones::getNames('pl'))])
            ->add('locale', ChoiceType::class, ['label' => 'Locale', 'choices' => array_flip(Locales::getNames('pl'))])
            ->add('week_start', ChoiceType::class, ['label' => 'Pierwszy dzień tygodnia', 'choices' => ['Poniedziałek' => 1, 'Niedziela' => 0]])
            ->add('language', ChoiceType::class, ['label' => 'Język domyślny', 'choices' => array_flip(Languages::getNames('pl'))])
            ->add('show_login', ChoiceType::class, $yesNo('Pokazuj logowanie'))
            ->add('show_search', ChoiceType::class, $yesNo('Pokazuj wyszukiwarkę'))
            ->add('show_breadcrumbs', ChoiceType::class, $yesNo('Pokazuj breadcrumbs'))
            ->add('show_language', ChoiceType::class, $yesNo('Pokazuj przełącznik języka'))
            ->add('eu_cookie', ChoiceType::class, $yesNo('Pokazuj komunikat cookies'))
            ->add('maintenance', ChoiceType::class, $yesNo('Tryb konserwacji'))
            ->add('maintenance_date', TextType::class, ['label' => 'Data zakończenia konserwacji', 'required' => false])
            ->add('maintenance_time', TextType::class, ['label' => 'Godzina zakończenia konserwacji', 'required' => false])
            ->add('maintenance_message', TextareaType::class, ['label' => 'Komunikat konserwacyjny', 'required' => false])
            ->add('thumbnail_width', IntegerType::class, $positive('Szerokość miniatury'))
            ->add('thumbnail_height', IntegerType::class, $positive('Wysokość miniatury'))
            ->add('image_width', IntegerType::class, $positive('Szerokość obrazu'))
            ->add('image_height', IntegerType::class, $positive('Wysokość obrazu'))
            ->add('small_image_width', IntegerType::class, $positive('Szerokość małego obrazu'))
            ->add('small_image_height', IntegerType::class, $positive('Wysokość małego obrazu'))
            ->add('medium_image_width', IntegerType::class, $positive('Szerokość średniego obrazu'))
            ->add('medium_image_height', IntegerType::class, $positive('Wysokość średniego obrazu'))
            ->add('avatar_width', IntegerType::class, $positive('Szerokość avatara'))
            ->add('avatar_height', IntegerType::class, $positive('Wysokość avatara'))
            ->add('image_quality', IntegerType::class, ['label' => 'Jakość obrazów (%)', 'constraints' => [new Range(min: 1, max: 100)]])
            ->add('per_page', IntegerType::class, $positive('Rekordów na stronę'))
            ->add('currency', TextType::class, ['label' => 'Kod waluty'])
            ->add('currency_symbol', TextType::class, ['label' => 'Symbol waluty'])
            ->add('thousands_separator', TextType::class, ['label' => 'Separator tysięcy', 'required' => false])
            ->add('decimal_separator', TextType::class, ['label' => 'Separator dziesiętny'])
            ->add('registration_allowed', ChoiceType::class, $yesNo('Zezwalaj na rejestrację'))
            ->add('registration_verify', ChoiceType::class, $yesNo('Weryfikacja adresu e-mail'))
            ->add('registration_auto_verify', ChoiceType::class, $yesNo('Automatyczna aktywacja kont'))
            ->add('notify_admin', ChoiceType::class, $yesNo('Powiadamiaj administratora'))
            ->add('user_limit', IntegerType::class, ['label' => 'Limit użytkowników (0 = bez limitu)', 'constraints' => [new Range(min: 0)]])
            ->add('login_attempts', IntegerType::class, $positive('Dozwolone próby logowania'))
            ->add('flood_seconds', IntegerType::class, ['label' => 'Ochrona flood (sekundy)', 'constraints' => [new Range(min: 0)]])
            ->add('logging', ChoiceType::class, $yesNo('Rejestruj zdarzenia systemowe'))
            ->add('alert_email_template', ChoiceType::class, ['label' => 'Szablon wiadomości alertu', 'choices' => ['Domyślny szablon systemowy' => 0]])
            ->add('facebook', TextType::class, ['label' => 'Facebook', 'required' => false])
            ->add('instagram', TextType::class, ['label' => 'Instagram', 'required' => false])
            ->add('twitter', TextType::class, ['label' => 'X / Twitter', 'required' => false])
            ->add('pinterest', TextType::class, ['label' => 'Pinterest', 'required' => false])
            ->add('linkedin', TextType::class, ['label' => 'LinkedIn', 'required' => false])
            ->add('youtube', TextType::class, ['label' => 'YouTube', 'required' => false])
            ->add('tiktok', TextType::class, ['label' => 'TikTok', 'required' => false])
            ->add('analytics', TextareaType::class, ['label' => 'Kod analityczny', 'required' => false])
            ->add('meta_keywords', TextareaType::class, ['label' => 'Domyślne słowa kluczowe', 'required' => false])
            ->add('meta_description', TextareaType::class, ['label' => 'Domyślny opis meta', 'required' => false])
            ->add('tenor_api_key', TextType::class, ['label' => 'Klucz API Tenor', 'required' => false])
            ->add('api_auth_module', ChoiceType::class, ['label' => 'Moduł autoryzacji API', 'choices' => ['Brak' => 'none']])
            ->add('mailer', ChoiceType::class, ['label' => 'Sposób wysyłki', 'choices' => ['PHP Mailer' => 'PHP', 'Sendmail' => 'SMAIL', 'SMTP' => 'SMTP']])
            ->add('sendmail_path', TextType::class, ['label' => 'Ścieżka Sendmail', 'required' => false])
            ->add('smtp_host', TextType::class, ['label' => 'Host SMTP', 'required' => false])
            ->add('smtp_user', TextType::class, ['label' => 'Użytkownik SMTP', 'required' => false])
            ->add('smtp_password', PasswordType::class, ['label' => 'Nowe hasło SMTP', 'mapped' => false, 'required' => false, 'help' => 'Pozostaw puste, aby zachować zapisane hasło.', 'constraints' => [new Length(max: 255)]])
            ->add('smtp_port', IntegerType::class, ['label' => 'Port SMTP', 'required' => false, 'constraints' => [new Range(min: 1, max: 65535)]])
            ->add('smtp_ssl', ChoiceType::class, $yesNo('Połączenie SSL'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, 'csrf_token_id' => 'system-settings']);
    }
}
