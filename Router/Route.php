<?php

namespace Softadastra\Router;

use Softadastra\Config\Database;

class Route
{
    public $path;
    public $action;
    public $matches = [];
    public $queryParams = [];

    private $paramNames = [];

    public function __construct($path, $action)
    {
        $this->path = trim($path, '/');
        $this->action = $action;
    }

    public function matches(string $url)
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);

        preg_match_all('#:([\w]+)#', $this->path, $paramMatches);
        $this->paramNames = $paramMatches[1];

        $regex = preg_replace('#:([\w]+)#', '([^/]+)', $this->path);
        $pattern = "#^{$regex}$#";

        if (preg_match($pattern, $url, $valueMatches)) {
            array_shift($valueMatches);
            $this->matches = [];

            foreach ($this->paramNames as $index => $name) {
                $this->matches[$name] = htmlspecialchars($valueMatches[$index], ENT_QUOTES, 'UTF-8');
            }

            return true;
        }

        return false;
    }

    public function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;
    }

    private function render(string $path, string $layout, array $params = []): void
    {
        ob_start();

        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $filePath = VIEWS . $path . '.php';

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "View not found: $filePath";
            exit;
        }

        extract($params);
        require $filePath;

        $content = ob_get_clean();
        require VIEWS . $layout;
    }

    public function execute()
    {
        $params = explode('@', $this->action);
        $controller = new $params[0](Database::getInstance(DB_NAME, DB_HOST, DB_USER, DB_PWD));
        $method = $params[1];

        try {
            $paramsToPass = array_merge($this->matches, $this->queryParams);
            $reflection = new \ReflectionMethod($controller, $method);
            $paramsCount = $reflection->getNumberOfParameters();

            if ($paramsCount > 0) {
                $methodParams = $reflection->getParameters();
                $finalParams = [];

                foreach ($methodParams as $param) {
                    $paramName = $param->getName();

                    if (isset($this->queryParams[$paramName])) {
                        $finalParams[] = $this->queryParams[$paramName];
                    } elseif (isset($this->matches[$paramName])) {
                        $value = $this->matches[$paramName];

                        if ($param->hasType() && $param->getType()->getName() === 'int') {
                            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                                throw new \InvalidArgumentException("Le paramètre '$paramName' doit être un entier.");
                            }
                            $finalParams[] = (int) $value;
                        } else {
                            $finalParams[] = $value;
                        }
                    } else {
                        $finalParams[] = null;
                    }
                }

                return call_user_func_array([$controller, $method], $finalParams);
            } else {
                return $controller->$method();
            }
        } catch (\InvalidArgumentException $e) {
            $this->renderErrorPage("Erreur : " . $e->getMessage());
        } catch (\Exception $e) {
            $this->renderErrorPage("Exception: " . $e->getMessage());
        }
    }

    private function renderErrorPage($message)
    {
        http_response_code(500);
        $this->render('errors.errors', 'errors.php', ['errorMessage' => $message]);
    }
}
