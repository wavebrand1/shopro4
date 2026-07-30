<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Form;

use App\Language\Application\SystemTranslator;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Settings\Application\FrontThemeRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

final class SystemSettingsType extends AbstractType
{
    public function __construct(private readonly FrontThemeRegistry $themes, private readonly EmailTemplateRepository $emailTemplates, private readonly SystemTranslator $translator) {}

    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $t = $this->translator->translate(...);
        $yesNo = static fn (string $label): array => ['label' => $label, 'choices' => [$t('common.yes') => true, $t('common.no') => false], 'expanded' => true, 'multiple' => false];
        $positive = static fn (string $label): array => ['label' => $label, 'constraints' => [new Positive()]];

        $b
            ->add('site_name', TextType::class, ['label' => $t('settings.site_name')])
            ->add('company', TextType::class, ['label' => $t('settings.company')])
            ->add('site_url', UrlType::class, ['label' => $t('settings.site_url'), 'constraints' => [new Url(requireTld: false)]])
            ->add('site_dir', TextType::class, ['label' => $t('settings.site_dir'), 'help' => $t('settings.site_dir_help'), 'required' => false])
            ->add('site_email', EmailType::class, ['label' => $t('settings.site_email'), 'constraints' => [new Email()]])
            ->add('site_phone', TextType::class, ['label' => 'Telefon witryny', 'required' => false])
            ->add('site_address', TextareaType::class, ['label' => 'Adres firmy', 'required' => false, 'help' => 'Każdy wiersz będzie wyświetlany w nowej linii.'])
            ->add('site_logo_file', FileType::class, ['label' => $t('settings.site_logo'), 'mapped' => false, 'required' => false, 'help' => $t('settings.site_logo_help'), 'attr' => ['accept' => '.svg,.png,.jpg,.jpeg,.webp'], 'constraints' => [new File(maxSize: '3M')]])
            ->add('remove_site_logo', CheckboxType::class, ['label' => $t('settings.remove_logo'), 'mapped' => false, 'required' => false])
            ->add('favicon_file', FileType::class, ['label' => 'Favicon', 'mapped' => false, 'required' => false, 'help' => $t('settings.favicon_help'), 'attr' => ['accept' => '.png,.webp,.ico'], 'constraints' => [new File(maxSize: '1M')]])
            ->add('remove_favicon', CheckboxType::class, ['label' => $t('settings.remove_favicon'), 'mapped' => false, 'required' => false])
            ->add('theme', ChoiceType::class, ['label' => $t('settings.front_theme'), 'choices' => $this->themes->frontChoices($this->translator->locale())])
            ->add('theme_variant', ChoiceType::class, ['label' => $t('settings.theme_variant'), 'choices' => $this->themes->frontVariantChoices($this->translator->locale())])
            ->add('admin_theme', ChoiceType::class, ['label' => $t('settings.admin_theme'), 'choices' => $this->themes->adminChoices($this->translator->locale())])
            ->add('admin_theme_variant', ChoiceType::class, ['label' => $t('settings.admin_theme_variant'), 'choices' => $this->themes->adminVariantChoices($this->translator->locale())])
            ->add('date_short', ChoiceType::class, ['label' => $t('settings.date_short'), 'choices' => ['MM-DD-YYYY' => '%m-%d-%Y', 'D-MM-YYYY' => '%e-%m-%Y', 'MM-D-YY' => '%m-%e-%y', 'D-MMM-YY' => '%e-%m-%y', 'DD Mon YYYY' => '%d %b %Y']])
            ->add('date_long', ChoiceType::class, ['label' => $t('settings.date_long'), 'choices' => ['Month DD, YYYY HH:MM AM' => '%B %d, %Y %I:%M %p', 'DD Month YYYY HH:MM AM' => '%d %B %Y %I:%M %p', 'Month DD, YYYY' => '%B %d, %Y', 'DD Month, YYYY' => '%d %B, %Y', 'Day DD Month YYYY' => '%A %d %B %Y', 'Day DD Month YYYY HH:MM' => '%A %d %B %Y %H:%M', 'Day DD, Month' => '%a %d, %B']])
            ->add('time_format', ChoiceType::class, ['label' => $t('settings.time_format'), 'choices' => ['12h (02:30 PM)' => '%I:%M %p', '12h (02:30 pm)' => '%I:%M %P', '24h (14:30)' => '%H:%M', 'Hour (14)' => '%k']])
            ->add('timezone', ChoiceType::class, ['label' => $t('settings.timezone'), 'choices' => array_flip(Timezones::getNames($this->translator->locale()))])
            ->add('locale', ChoiceType::class, ['label' => 'Locale', 'choices' => array_flip(Locales::getNames($this->translator->locale()))])
            ->add('week_start', ChoiceType::class, ['label' => $t('settings.week_start'), 'choices' => [$t('settings.monday') => 1, $t('settings.sunday') => 0]])
            ->add('language', ChoiceType::class, ['label' => $t('language.default'), 'choices' => array_flip(Languages::getNames($this->translator->locale()))])
            ->add('show_login', ChoiceType::class, $yesNo($t('settings.show_login')))
            ->add('show_search', ChoiceType::class, $yesNo($t('settings.show_search')))
            ->add('show_breadcrumbs', ChoiceType::class, $yesNo($t('settings.show_breadcrumbs')))
            ->add('show_language', ChoiceType::class, $yesNo($t('settings.show_language')))
            ->add('eu_cookie', ChoiceType::class, $yesNo($t('settings.eu_cookie')))
            ->add('maintenance', ChoiceType::class, $yesNo($t('settings.maintenance')))
            ->add('maintenance_date', TextType::class, ['label' => $t('settings.maintenance_date'), 'required' => false, 'attr' => ['type' => 'date']])
            ->add('maintenance_time', TextType::class, ['label' => $t('settings.maintenance_time'), 'required' => false, 'attr' => ['type' => 'time']])
            ->add('maintenance_message', TextareaType::class, ['label' => $t('settings.maintenance_message'), 'required' => false])
            ->add('image_formats', ChoiceType::class, ['label' => $t('settings.image_formats'), 'choices' => ['AVIF' => 'avif', 'WebP' => 'webp'], 'expanded' => true, 'multiple' => true])
            ->add('image_widths', TextType::class, ['label' => $t('settings.image_widths'), 'help' => $t('settings.image_widths_help')])
            ->add('image_quality', IntegerType::class, ['label' => $t('settings.image_quality'), 'constraints' => [new Range(min: 1, max: 100)]])
            ->add('image_lazy_loading', ChoiceType::class, $yesNo($t('settings.image_lazy_loading')))
            ->add('per_page', IntegerType::class, $positive($t('settings.per_page')))
            ->add('currency', TextType::class, ['label' => $t('settings.currency')])
            ->add('currency_symbol', TextType::class, ['label' => $t('language.currency_symbol')])
            ->add('thousands_separator', TextType::class, ['label' => $t('language.thousands_separator'), 'required' => false])
            ->add('decimal_separator', TextType::class, ['label' => $t('language.decimal_separator')])
            ->add('registration_allowed', ChoiceType::class, $yesNo($t('settings.registration_allowed')))
            ->add('registration_verify', ChoiceType::class, $yesNo($t('settings.registration_verify')))
            ->add('registration_auto_verify', ChoiceType::class, $yesNo($t('settings.registration_auto_verify')))
            ->add('notify_admin', ChoiceType::class, $yesNo($t('settings.notify_admin')))
            ->add('user_limit', IntegerType::class, ['label' => $t('settings.user_limit'), 'constraints' => [new Range(min: 0)]])
            ->add('login_attempts', IntegerType::class, $positive($t('settings.login_attempts')))
            ->add('flood_seconds', IntegerType::class, ['label' => $t('settings.flood_seconds'), 'constraints' => [new Range(min: 0)]])
            ->add('logging', ChoiceType::class, $yesNo($t('settings.logging')))
            ->add('alert_email_template', ChoiceType::class, ['label' => $t('settings.alert_email_template'), 'choices' => $this->emailTemplates->choices()])
            ->add('facebook', TextType::class, ['label' => 'Facebook', 'required' => false])
            ->add('instagram', TextType::class, ['label' => 'Instagram', 'required' => false])
            ->add('twitter', TextType::class, ['label' => 'X / Twitter', 'required' => false])
            ->add('pinterest', TextType::class, ['label' => 'Pinterest', 'required' => false])
            ->add('linkedin', TextType::class, ['label' => 'LinkedIn', 'required' => false])
            ->add('youtube', TextType::class, ['label' => 'YouTube', 'required' => false])
            ->add('tiktok', TextType::class, ['label' => 'TikTok', 'required' => false])
            ->add('analytics_measurement_id', TextType::class, ['label' => 'Google Analytics Measurement ID', 'required' => false, 'help' => $t('settings.analytics_help')])
            ->add('analytics_consent_required', ChoiceType::class, $yesNo($t('settings.analytics_consent')))
            ->add('meta_keywords', TextareaType::class, ['label' => $t('settings.meta_keywords'), 'required' => false])
            ->add('meta_description', TextareaType::class, ['label' => $t('settings.meta_description'), 'required' => false])
            ->add('social_image_file', FileType::class, ['label' => $t('settings.social_image'), 'mapped' => false, 'required' => false, 'help' => $t('settings.social_image_help'), 'attr' => ['accept' => '.png,.jpg,.jpeg,.webp'], 'constraints' => [new File(maxSize: '5M')]])
            ->add('remove_social_image', CheckboxType::class, ['label' => $t('settings.remove_social_image'), 'mapped' => false, 'required' => false])
            ->add('smtp_host', TextType::class, ['label' => 'SMTP host', 'required' => false])
            ->add('smtp_user', TextType::class, ['label' => $t('settings.smtp_user'), 'required' => false])
            ->add('smtp_password', PasswordType::class, ['label' => $t('settings.smtp_password'), 'mapped' => false, 'required' => false, 'help' => $t('settings.smtp_password_help'), 'constraints' => [new Length(max: 255)]])
            ->add('smtp_port', IntegerType::class, ['label' => 'Port SMTP', 'required' => false, 'constraints' => [new Range(min: 1, max: 65535)]])
            ->add('smtp_encryption', ChoiceType::class, ['label' => $t('settings.smtp_encryption'), 'choices' => [$t('settings.starttls') => 'tls', $t('settings.smtps') => 'smtps', $t('settings.no_encryption') => 'none']])
            ->add('mail_from_address', EmailType::class, ['label' => $t('settings.from_address'), 'required' => false, 'constraints' => [new Email()]])
            ->add('mail_from_name', TextType::class, ['label' => $t('settings.from_name')])
            ->add('mail_reply_to', EmailType::class, ['label' => 'Reply-To', 'required' => false, 'constraints' => [new Email()]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, 'csrf_token_id' => 'system-settings']);
    }
}
