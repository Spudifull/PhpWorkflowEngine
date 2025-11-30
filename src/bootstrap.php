<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    new Dotenv()->load($envFile);
}

$container = new ContainerBuilder();
$configDir = new FileLocator(__DIR__ . '/../config');
$loader = new PhpFileLoader($container, $configDir);

try {
    $loader->load('services.php');
    $container->compile();
} catch (Exception $e) {
    throw new RuntimeException(
        sprintf('Failed to build container: %s', $e->getMessage()),
        0,
        $e
    );
}

return $container;