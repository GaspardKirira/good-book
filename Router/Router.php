<?php

namespace Softadastra\Router;

use Softadastra\Exception\NotFoundException;

class Router
{
    public $url;
    public $queryParams = [];
    public $routes = [];

    public function __construct($url)
    {
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $this->queryParams);
        }
        $this->url = isset($parsedUrl['path']) ? trim($parsedUrl['path'], '/') : '';
    }

    public function get(string $path, string $action)
    {
        $this->routes['GET'][] = new Route($path, $action);
    }

    public function post(string $path, string $action)
    {
        $this->routes['POST'][] = new Route($path, $action);
    }

    public function put(string $path, string $action)
    {
        $this->routes['PUT'][] = new Route($path, $action);
    }

    public function delete(string $path, string $action)
    {
        $this->routes['DELETE'][] = new Route($path, $action);
    }

    private function renderError(string $path, string $layout, array $params = []): void
    {
        ob_start();

        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $filePath = VIEWS . $path . '.php';

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Erreur : Vue introuvable → $filePath";
            exit;
        }

        extract($params);
        require $filePath;

        $content = ob_get_clean();
        require VIEWS . $layout;
    }

    public function run()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];

            if (!isset($this->routes[$method])) {
                throw new NotFoundException("Méthode HTTP non supportée : $method");
            }

            foreach ($this->routes[$method] as $route) {
                if ($route->matches($this->url)) {
                    $route->setQueryParams($this->queryParams);
                    return $route->execute();
                }
            }

            throw new NotFoundException("Page non trouvée.");
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->renderError('errors.errors', 'errors.php', [
                'errorMessage' => $e->getMessage()
            ]);
            exit;
        }
    }
}
