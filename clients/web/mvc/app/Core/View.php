<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rendert PHP-Views mit einem gemeinsamen Layout.
 */
class View
{
    public function __construct(private string $basePath)
    {
    }

    /**
     * Rendert eine View direkt in einen String.
     */
    public function render(string $view, array $data = [], string $layout = 'layouts/main'): string
    {
        $viewFile = $this->basePath . '/' . $view . '.php';
        $layoutFile = $this->basePath . '/' . $layout . '.php';

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }
}
