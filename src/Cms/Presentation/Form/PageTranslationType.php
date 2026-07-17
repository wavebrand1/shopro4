<?php
declare(strict_types=1);
namespace App\Cms\Presentation\Form;
use App\Cms\Domain\Entity\PageTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
final class PageTranslationType extends AbstractType
{
 public function buildForm(FormBuilderInterface $b,array $o):void{$b
  ->add('title',TextType::class,['label'=>'Tytuł','constraints'=>[new Assert\NotBlank()]])->add('slug',TextType::class,['label'=>'Slug','constraints'=>[new Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]])
  ->add('caption',TextareaType::class,['label'=>'Podpis / zajawka','required'=>false])->add('seoTitle',TextType::class,['label'=>'Tytuł SEO','required'=>false])->add('description',TextareaType::class,['label'=>'Opis meta','required'=>false])->add('canonical',TextType::class,['label'=>'Canonical','required'=>false])
  ->add('content',HiddenType::class,['required'=>false])->add('builderData',HiddenType::class,['required'=>false])->add('builderCss',HiddenType::class,['required'=>false])->add('published',CheckboxType::class,['label'=>'Opublikowane tłumaczenie','required'=>false]);}
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>PageTranslation::class]);}
}
