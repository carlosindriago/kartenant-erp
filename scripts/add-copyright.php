<?php

$dir = new RecursiveDirectoryIterator(__DIR__.'/app');
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$header = <<<'HEADER'
/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

HEADER;

$count = 0;
foreach ($files as $file) {
    $filePath = $file[0];
    $content = file_get_contents($filePath);

    // Skip if it already has copyright or if it's not a PHP file starting with <?php
    if (strpos($content, 'Kartenant') !== false || strpos($content, "<?php\n") !== 0) {
        continue;
    }

    $newContent = "<?php\n\n".$header.substr($content, 6);
    file_put_contents($filePath, $newContent);
    $count++;
}

echo "Aviso de copyright añadido a $count archivos.\n";
