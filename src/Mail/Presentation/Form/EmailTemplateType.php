<?php
declare(strict_types=1);
namespace App\Mail\Presentation\Form;

use App\Mail\Application\SystemEmailTemplateCatalog;
use App\Mail\Domain\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailTemplateType extends AbstractType
{
    public function __construct(private readonly SystemEmailTemplateCatalog $catalog) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', ChoiceType::class, [
                'label'=>'Zdarzenie / kod techniczny', 'choices'=>$this->catalog->choices(),
                'placeholder'=>'Wybierz zastosowanie szablonu', 'attr'=>['data-email-template-code'=>true],
                'choice_attr'=>fn ($choice, $key, $value): array => ['data-description'=>$this->catalog->description((string) $value)],
            ])
            ->add('name', TextType::class, ['label'=>'Nazwa'])
            ->add('subject', TextType::class, ['label'=>'Temat'])
            ->add('content', TextareaType::class, ['label'=>'Treść HTML','attr'=>['rows'=>18,'data-rich-editor'=>true]]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class'=>EmailTemplate::class]); }
}
