<?php
/**
 * User: focus
 * Date: 04.10.18
 * Time: 11:13
 */

declare(strict_types=1);
const PROJECT_ROOT = __DIR__;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/cfg/Config.php';

spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    include __DIR__ . "/lib/{$class}.php";
});

\FordRT\Dropbox::download();
\FordRT\Kpass::parse();
new \FordRT\GSpreadsheets();
