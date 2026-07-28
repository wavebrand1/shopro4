<?php

declare(strict_types=1);

namespace App\Newsletter\Presentation\Form;

use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use App\Identity\Domain\Entity\SiteUser;
use App\Language\Application\SystemTranslator;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

final class NewsletterCampaignType extends AbstractType
{
    public function __construct(private readonly SiteUserRepository $users, private readonly SystemTranslator $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translator->translate(...);
        $campaign = $options['data'];
        $selectedIds = $campaign instanceof NewsletterCampaign ? $campaign->getSelectedSiteUserIds() : [];
        $selectedUsers = $selectedIds === [] ? [] : $this->users->findBy(['id' => $selectedIds], ['username' => 'ASC']);
        $builder
            ->add('subject', TextType::class, ['label' => $t('newsletter.subject'), 'constraints' => [new NotBlank()]])
            ->add('includeSubscribers', CheckboxType::class, ['label' => $t('newsletter.include_subscribers'), 'required' => false])
            ->add('selectedSiteUserIds', EntityType::class, [
                'label' => $t('newsletter.selected_users'),
                'class' => SiteUser::class,
                'choice_label' => static fn (SiteUser $user): string => $user->getUsername().' — '.$user->getEmail(),
                'query_builder' => static fn (SiteUserRepository $repository) => $repository->createQueryBuilder('user')->andWhere('user.active = true')->orderBy('user.username', 'ASC'),
                'choice_lazy' => true,
                'multiple' => true,
                'expanded' => false,
                'mapped' => false,
                'data' => $selectedUsers,
                'required' => false,
                'help' => $t('newsletter.selected_users_help'),
                'attr' => [
                    'data-searchable-select' => true,
                    'data-search-url' => '/admin/newsletter/users/search',
                    'data-search-placeholder' => $t('newsletter.user_search_placeholder'),
                    'data-search-empty' => $t('newsletter.user_search_empty'),
                    'data-search-more' => $t('newsletter.user_search_more'),
                    'data-remove-label' => $t('common.remove'),
                ],
            ])
            ->add('customEmails', TextareaType::class, ['label' => $t('newsletter.custom_emails'), 'required' => false, 'help' => $t('newsletter.custom_emails_help'), 'attr' => ['rows' => 5], 'constraints' => [new All([new Email()])]])
            ->add('recipientFile', FileType::class, ['label' => $t('newsletter.recipient_csv'), 'mapped' => false, 'required' => false, 'help' => $t('newsletter.recipient_csv_help'), 'constraints' => [new File(maxSize: '2M', mimeTypes: ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'], mimeTypesMessage: $t('newsletter.recipient_csv_invalid'))]])
            ->add('content', TextareaType::class, ['label' => $t('newsletter.html_content'), 'attr' => ['rows' => 16, 'data-rich-editor' => true], 'constraints' => [new NotBlank()]]);
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $campaign = $event->getData();
            if (!$campaign instanceof NewsletterCampaign) return;
            $users = $event->getForm()->get('selectedSiteUserIds')->getData();
            $campaign->setSelectedSiteUserIds(array_map(
                static fn (SiteUser $user): int => (int) $user->getId(),
                $users instanceof \Traversable ? iterator_to_array($users) : (is_array($users) ? $users : []),
            ));
        });
        $builder->get('customEmails')->addModelTransformer(new CallbackTransformer(
            static fn (array $emails): string => implode("\n", $emails),
            static fn (?string $value): array => array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $value) ?: [])))
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NewsletterCampaign::class]);
    }
}
