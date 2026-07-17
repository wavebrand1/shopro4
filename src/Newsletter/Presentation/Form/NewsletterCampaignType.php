<?php
declare(strict_types=1);
namespace App\Newsletter\Presentation\Form;

use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
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
    public function __construct(private readonly AdminUserRepository $users) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices=[];
        foreach ($this->users->findBy([], ['username'=>'ASC']) as $user) $choices[$user->getDisplayName().' — '.$user->getEmail()]=$user->getId();
        $builder
            ->add('subject', TextType::class, ['label'=>'Temat','constraints'=>[new NotBlank()]])
            ->add('includeSubscribers', CheckboxType::class, ['label'=>'Wyślij do wszystkich aktywnych użytkowników zapisanych do newslettera','required'=>false])
            ->add('selectedUserIds', ChoiceType::class, ['label'=>'Wybrani użytkownicy','choices'=>$choices,'multiple'=>true,'expanded'=>true,'required'=>false,'help'=>'Możesz wskazać użytkowników niezależnie od ich ustawienia Newsletter.'])
            ->add('customEmails', TextareaType::class, ['label'=>'Dodatkowe adresy e-mail','required'=>false,'help'=>'Jeden adres w wierszu albo adresy oddzielone przecinkami.','attr'=>['rows'=>5],'constraints'=>[new All([new Email()])]])
            ->add('content', TextareaType::class, ['label'=>'Treść HTML','attr'=>['rows'=>16,'data-rich-editor'=>true],'constraints'=>[new NotBlank()]]);
        $builder->get('customEmails')->addModelTransformer(new CallbackTransformer(
            static fn(array $emails): string => implode("\n", $emails),
            static fn(?string $value): array => array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $value) ?: [])))
        ));
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class'=>NewsletterCampaign::class]); }
}
