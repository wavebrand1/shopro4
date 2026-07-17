<?php
declare(strict_types=1);
namespace App\Language\Application\Command;
use App\Language\Application\SystemTranslationCatalog;use App\Language\Domain\Entity\Language;use App\Language\Domain\Entity\Translation;use Doctrine\ORM\EntityManagerInterface;use Symfony\Component\Console\Attribute\AsCommand;use Symfony\Component\Console\Command\Command;use Symfony\Component\Console\Input\InputInterface;use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name:'app:translations:sync',description:'Uzupełnia brakujące języki i systemowe frazy tłumaczeń.')]
final class SyncTranslationsCommand extends Command
{
 public function __construct(private readonly EntityManagerInterface $em){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output):int{
  $languages=[];foreach(['pl','en'] as $code){$language=$this->em->getRepository(Language::class)->findOneBy(['code'=>$code]);if(!$language){$language=new Language();$language->setCode($code);$language->setName($code==='pl'?'Polski':'English');$language->setLocale($code==='pl'?'pl_PL':'en_GB');$language->setCurrency($code==='pl'?'PLN':'GBP');$language->setCurrencySymbol($code==='pl'?'zł':'£');$language->setAuthor('WaveBrand');$language->setActive(true);$language->setDefaultLanguage($code==='pl');$this->em->persist($language);$this->em->flush();}$languages[$code]=$language;}
  $added=0;foreach(SystemTranslationCatalog::phrases() as $key=>$values)foreach($languages as $code=>$language)if(!$this->em->getRepository(Translation::class)->findOneBy(['language'=>$language,'key'=>$key])){$translation=new Translation($language,$key);$translation->setValue($values[$code]);$this->em->persist($translation);$added++;}
  $this->em->flush();$output->writeln(sprintf('<info>Zsynchronizowano katalog PL/EN. Dodano %d brakujących fraz.</info>',$added));return Command::SUCCESS;
 }
}
