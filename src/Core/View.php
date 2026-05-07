<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Configures and renders Twig templates for the application.
 */
class View
{

    /**
     * Cached Twig environment instance.
     */
    private static ?Environment $twig = null;

    /**
     * Returns the shared Twig environment.
     */
    public static function get(): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../View');

            self::$twig = new Environment($loader, [
                'cache' => false,
                'debug' => true
            ]);

            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            $base = in_array($base, ['.', '/'], true) ? '' : $base;

            self::$twig->addGlobal('base', $base);
            self::$twig->addGlobal('currentPage', $_GET['page'] ?? 'cars');
            self::$twig->addGlobal('user', $_SESSION['user'] ?? null);
        }

        return self::$twig;
    }

    /**
     * Renders a Twig template with the provided data.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {

        $twig = self::get();
        $twig->addGlobal('currentPage', $_GET['page'] ?? 'cars');
        $twig->addGlobal('user', $_SESSION['user'] ?? null);

        echo $twig->render($template, $data);
    }
}
