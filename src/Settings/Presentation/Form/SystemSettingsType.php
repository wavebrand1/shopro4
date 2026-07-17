<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

final class SystemSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $checkbox = ['required' => false];
        $b
            ->add('site_name', TextType::class, ['label' => 'Nazwa witryny'])
            ->add('company', TextType::class, ['label' => 'Nazwa firmy', 'required' => false])
            ->add('site_url', UrlType::class, ['label' => 'Adres witryny', 'constraints' => [new Url()]])
            ->add('site_email', EmailType::class, ['label' => 'E-mail witryny', 'constraints' => [new Email()]])
            ->add('theme', TextType::class, ['label' => 'Szablon', 'required' => false])
            ->add('locale', TextType::class, ['label' => 'Locale', 'help' => 'Np. pl_PL'])
            ->add('timezone', TextType::class, ['label' => 'Strefa czasowa', 'help' => 'Np. Europe/Warsaw'])
            ->add('language', TextType::class, ['label' => 'Język domyślny', 'help' => 'Dwuliterowy kod, np. pl'])
            ->add('date_short', TextType::class, ['label' => 'Krótki format daty'])
            ->add('date_long', TextType::class, ['label' => 'Długi format daty'])
            ->add('time_format', ChoiceType::class, ['label' => 'Format czasu', 'choices' => ['24-godzinny' => 'H:i', '12-godzinny' => 'h:i a']])
            ->add('week_start', ChoiceType::class, ['label' => 'Pierwszy dzień tygodnia', 'choices' => ['Poniedziałek' => 1, 'Niedziela' => 0]])
            ->add('show_login', CheckboxType::class, $checkbox + ['label' => 'Pokazuj logowanie'])
            ->add('show_search', CheckboxType::class, $checkbox + ['label' => 'Pokazuj wyszukiwarkę'])
            ->add('show_breadcrumbs', CheckboxType::class, $checkbox + ['label' => 'Pokazuj breadcrumbs'])
            ->add('show_language', CheckboxType::class, $checkbox + ['label' => 'Pokazuj przełącznik języka'])
            ->add('eu_cookie', CheckboxType::class, $checkbox + ['label' => 'Pokazuj komunikat cookies'])
            ->add('maintenance', CheckboxType::class, $checkbox + ['label' => 'Tryb konserwacji'])
            ->add('maintenance_from', TextType::class, ['label' => 'Planowana data i godzina', 'required' => false])
            ->add('maintenance_message', TextareaType::class, ['label' => 'Komunikat konserwacyjny', 'required' => false])
            ->add('image_width', IntegerType::class, ['label' => 'Szerokość obrazu', 'constraints' => [new Positive()]])
            ->add('image_height', IntegerType::class, ['label' => 'Wysokość obrazu', 'constraints' => [new Positive()]])
            ->add('thumbnail_width', IntegerType::class, ['label' => 'Szerokość miniatury', 'constraints' => [new Positive()]])
            ->add('thumbnail_height', IntegerType::class, ['label' => 'Wysokość miniatury', 'constraints' => [new Positive()]])
            ->add('avatar_width', IntegerType::class, ['label' => 'Szerokość avatara', 'constraints' => [new Positive()]])
            ->add('avatar_height', IntegerType::class, ['label' => 'Wysokość avatara', 'constraints' => [new Positive()]])
            ->add('image_quality', IntegerType::class, ['label' => 'Jakość obrazów (%)', 'constraints' => [new Range(min: 1, max: 100)]])
            ->add('per_page', IntegerType::class, ['label' => 'Rekordów na stronę', 'constraints' => [new Positive()]])
            ->add('currency', TextType::class, ['label' => 'Kod waluty'])
            ->add('currency_symbol', TextType::class, ['label' => 'Symbol waluty'])
            ->add('registration_allowed', CheckboxType::class, $checkbox + ['label' => 'Zezwalaj na rejestrację'])
            ->add('registration_verify', CheckboxType::class, $checkbox + ['label' => 'Weryfikacja adresu e-mail'])
            ->add('registration_auto_verify', CheckboxType::class, $checkbox + ['label' => 'Automatyczna aktywacja kont'])
            ->add('notify_admin', CheckboxType::class, $checkbox + ['label' => 'Powiadamiaj administratora'])
            ->add('user_limit', IntegerType::class, ['label' => 'Limit użytkowników (0 = bez limitu)', 'constraints' => [new Range(min: 0)]])
            ->add('login_attempts', IntegerType::class, ['label' => 'Dozwolone próby logowania', 'constraints' => [new Positive()]])
            ->add('flood_seconds', IntegerType::class, ['label' => 'Ochrona flood (sekundy)', 'constraints' => [new Range(min: 0)]])
            ->add('logging', CheckboxType::class, $checkbox + ['label' => 'Rejestruj zdarzenia systemowe'])
            ->add('facebook', UrlType::class, ['label' => 'Facebook', 'required' => false])
            ->add('instagram', UrlType::class, ['label' => 'Instagram', 'required' => false])
            ->add('twitter', UrlType::class, ['label' => 'X / Twitter', 'required' => false])
            ->add('pinterest', UrlType::class, ['label' => 'Pinterest', 'required' => false])
            ->add('meta_keywords', TextareaType::class, ['label' => 'Domyślne słowa kluczowe', 'required' => false])
            ->add('meta_description', TextareaType::class, ['label' => 'Domyślny opis meta', 'required' => false])
            ->add('analytics', TextareaType::class, ['label' => 'Kod analityczny', 'required' => false])
            ->add('mailer', ChoiceType::class, ['label' => 'Sposób wysyłki', 'choices' => ['PHP Mailer' => 'PHP', 'Sendmail' => 'SMAIL', 'SMTP' => 'SMTP']])
            ->add('sendmail_path', TextType::class, ['label' => 'Ścieżka Sendmail', 'required' => false])
            ->add('smtp_host', TextType::class, ['label' => 'Host SMTP', 'required' => false])
            ->add('smtp_user', TextType::class, ['label' => 'Użytkownik SMTP', 'required' => false])
            ->add('smtp_password', PasswordType::class, ['label' => 'Nowe hasło SMTP', 'mapped' => false, 'required' => false, 'help' => 'Pozostaw puste, aby zachować zapisane hasło.', 'constraints' => [new Length(max: 255)]])
            ->add('smtp_port', IntegerType::class, ['label' => 'Port SMTP', 'required' => false, 'constraints' => [new Range(min: 1, max: 65535)]])
            ->add('smtp_encryption', ChoiceType::class, ['label' => 'Szyfrowanie SMTP', 'choices' => ['Brak' => '', 'TLS' => 'tls', 'SSL' => 'ssl']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, 'csrf_token_id' => 'system-settings']);
    }
}
