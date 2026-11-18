<?php

declare(strict_types=1);

use App\Cms\Domain\Entity\Page;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);
(new Dotenv())->bootEnv($projectDir.'/.env');
$kernel = new Kernel('prod', false);
$kernel->boot();

$apply = in_array('--apply', $argv, true);
$source = 'documentation/fotograftrzciana-restore';
foreach ($argv as $index => $argument) {
    if ($argument === '--source-dir' && isset($argv[$index + 1])) $source = $argv[$index + 1];
}
if (!str_starts_with($source, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $source)) {
    $source = $projectDir.DIRECTORY_SEPARATOR.$source;
}
$source = rtrim($source, DIRECTORY_SEPARATOR);

$pages = [
    'zdjecie-do-dowodu-osobistego' => 'document-id-builder_data-v1.json',
    'zdjecie-do-paszportu' => 'document-passport-builder_data-v1.json',
    'zdjecie-do-prawa-jazdy' => 'document-driving-licence-builder_data-v1.json',
    'zdjecie-do-karty-pobytu' => 'document-residence-card-builder_data-v1.json',
    'zdjecie-do-wizy' => 'document-visa-builder_data-v1.json',
    'zdjecie-do-legitymacji-szkolnej' => 'document-school-id-builder_data-v1.json',
    'zdjecie-do-legitymacji-studenckiej' => 'document-student-id-builder_data-v1.json',
    'zdjecie-do-karty-kierowcy' => 'document-driver-card-builder_data-v1.json',
    'zdjecie-do-dyplomu' => 'document-diploma-builder_data-v1.json',
    'zdjecie-do-akt-wojskowych' => 'document-military-records-builder_data-v1.json',
    'zdjecie-do-legitymacji-strazy-granicznej' => 'document-border-guard-builder_data-v1.json',
];

$doctrine = $kernel->getContainer()->get('doctrine');
$entityManager = $doctrine->getManager();
$repository = $entityManager->getRepository(Page::class);
$prepared = [];
$errors = [];
foreach ($pages as $slug => $filename) {
    $page = $repository->findOneBy(['slug' => $slug]);
    $path = $source.DIRECTORY_SEPARATOR.$filename;
    if (!$page instanceof Page) { $errors[] = $slug.': brak podstrony'; continue; }
    if (!is_file($path)) { $errors[] = $slug.': brak pliku '.$filename; continue; }
    $json = (string) file_get_contents($path);
    try { $decoded = json_decode($json, true, 100, JSON_THROW_ON_ERROR); }
    catch (Throwable $exception) { $errors[] = $slug.': błędny JSON — '.$exception->getMessage(); continue; }
    if (!is_array($decoded) || $decoded === []) { $errors[] = $slug.': pusty builder'; continue; }
    $prepared[$slug] = [$page, $json];
}

if ($errors !== []) {
    foreach ($errors as $error) echo "ERROR: {$error}\n";
    echo "Przerwano bez zapisu.\n";
    exit(1);
}
foreach ($prepared as $slug => [$page, $json]) {
    $same = hash_equals(hash('sha256', $page->getBuilderData()), hash('sha256', $json));
    echo $slug.': '.($same ? 'bez zmian' : 'do przywrócenia').PHP_EOL;
}
if (!$apply) {
    echo "Dry-run: nic nie zapisano. Dodaj --apply, aby przywrócić buildery.\n";
    exit(0);
}

$backupDir = $projectDir.'/var/restore-backups/fotograf-documents-'.date('Ymd-His');
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    echo "ERROR: nie można utworzyć {$backupDir}\n";
    exit(1);
}
foreach ($prepared as $slug => [$page]) {
    if (file_put_contents($backupDir.'/'.$slug.'.json', $page->getBuilderData()) === false) {
        echo "ERROR: nie można wykonać kopii {$slug}\n";
        exit(1);
    }
}

$connection = $entityManager->getConnection();
$connection->beginTransaction();
try {
    foreach ($prepared as [$page, $json]) { $page->setEditorMode('components'); $page->setBuilderData($json); }
    $entityManager->flush();
    $connection->commit();
} catch (Throwable $exception) {
    if ($connection->isTransactionActive()) $connection->rollBack();
    echo "ERROR: przywracanie nieudane — {$exception->getMessage()}\n";
    exit(1);
}
echo 'Przywrócono '.count($prepared).' podstron.'.PHP_EOL;
echo 'Kopia poprzednich builderów: '.$backupDir.PHP_EOL;
