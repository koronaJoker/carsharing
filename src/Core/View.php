<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{

    private static ?Environment $twig = null;

    public static function get(): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../View');

            self::$twig = new Environment($loader, [
                'cache' => false,
                'debug' => true
            ]);

            self::$twig->addGlobal('base', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
            self::$twig->addGlobal('currentPage', $_GET['page'] ?? 'cars');
            self::$twig->addGlobal('user', $_SESSION['user'] ?? null);
        }

        return self::$twig;
    }

    public static function render(string $template, array $data = []): void
    {

        $twig = self::get();
        $twig->addGlobal('currentPage', $_GET['page'] ?? 'cars');
        $twig->addGlobal('user', $_SESSION['user'] ?? null);

        echo $twig->render($template, $data);
    }
}
