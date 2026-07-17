<?php
declare(strict_types=1);
namespace App\Newsletter\Presentation\Form;

use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Language\Application\SystemTranslator;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class NewsletterCampaignType extends AbstractType
{
    public function __construct(private readonly AdminUserRepository $users, private readonly SystemTranslator $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices=[];
        $t=$this->translator->translate(...);
        foreach ($this->users->findBy([], ['username'=>'ASC']) as $user) $choices[$user->getDisplayName().' — '.$user->getEmail()]=$user->getId();
        $builder
            ->add('subject', TextType::class, ['label'=>$t('newsletter.subject'),'constraints'=>[new NotBlank()]])
            ->add('includeSubscribers', CheckboxType::class, ['label'=>$t('newsletter.include_subscribers'),'required'=>false])
            ->add('selectedUserIds', ChoiceType::class, ['label'=>$t('newsletter.selected_users'),'choices'=>$choices,'multiple'=>true,'expanded'=>true,'required'=>false,'help'=>$t('newsletter.selected_users_help')])
            ->add('customEmails', TextareaType::class, ['label'=>$t('newsletter.custom_emails'),'required'=>false,'help'=>$t('newsletter.custom_emails_help'),'attr'=>['rows'=>5],'constraints'=>[new All([new Email()])]])
            ->add('content', TextareaType::class, ['label'=>$t('newsletter.html_content'),'attr'=>['rows'=>16,'data-rich-editor'=>true],'constraints'=>[new NotBlank()]]);
        $builder->get('customEmails')->addModelTransformer(new CallbackTransformer(
            static fn(array $emails): string => implode("\n", $emails),
            static fn(?string $value): array => array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $value) ?: [])))
        ));
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class'=>NewsletterCampaign::class]); }
}
