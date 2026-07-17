<?php
declare(strict_types=1);
namespace App\Mail\Presentation\Form;
use App\Mail\Domain\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
final class EmailTemplateType extends AbstractType { public function buildForm(FormBuilderInterface $b,array $o):void { $b->add('code',TextType::class,['label'=>'Kod techniczny','help'=>'Np. account_activation'])->add('name',TextType::class,['label'=>'Nazwa'])->add('subject',TextType::class,['label'=>'Temat'])->add('content',TextareaType::class,['label'=>'Treść HTML','attr'=>['rows'=>18,'data-rich-editor'=>true]]); } public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>EmailTemplate::class]);} }
