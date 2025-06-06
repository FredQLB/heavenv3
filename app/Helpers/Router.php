<?php

namespace App\Helpers;

class Router
{
    private $routes = [];
    private $middlewares = [];

    public function __construct()
    {
        $this->middlewares['auth'] = function() {
            if (!Session::isLoggedIn()) {
                header('Location: /login');
                exit;
            }
        };

        $this->middlewares['megaadmin'] = function() {
            if (!Session::isMegaAdmin()) {
                header('Location: /error/403');
                exit;
            }
        };
    }

    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put($path, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete($path, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Nettoyer l'URI
        $requestUri = rtrim($requestUri, '/');
        if (empty($requestUri)) {
            $requestUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestUri, $params)) {
                // Exécuter les middlewares
                foreach ($route['middleware'] as $middlewareName) {
                    if (isset($this->middlewares[$middlewareName])) {
                        $this->middlewares[$middlewareName]();
                    }
                }

                // Exécuter le handler
                if (is_callable($route['handler'])) {
                    $route['handler']();
                } else if (is_string($route['handler']) && strpos($route['handler'], '@') !== false) {
                    list($controller, $method) = explode('@', $route['handler']);
                    $controllerClass = "App\\Controllers\\{$controller}";
                    
                    if (class_exists($controllerClass)) {
                        $controllerInstance = new $controllerClass();
                        if (method_exists($controllerInstance, $method)) {
                            // Passer les paramètres de route à la méthode
                            if (!empty($params)) {
                                $controllerInstance->$method(...array_values($params));
                            } else {
                                $controllerInstance->$method();
                            }
                        } else {
                            throw new \Exception("Méthode {$method} non trouvée dans {$controllerClass}");
                        }
                    } else {
                        throw new \Exception("Contrôleur {$controllerClass} non trouvé");
                    }
                }
                return;
            }
        }

        // Route non trouvée
        header('Location: /error/404');
        exit;
    }

    private function matchPath($routePath, $requestPath, &$params = [])
    {
        // Nettoyer les chemins
        $routePath = rtrim($routePath, '/');
        if (empty($routePath)) {
            $routePath = '/';
        }
        
        // Si pas de paramètres dans la route, correspondance exacte
        if (strpos($routePath, '{') === false) {
            return $routePath === $requestPath;
        }
        
        // Gérer les paramètres dynamiques
        $routeSegments = explode('/', $routePath);
        $requestSegments = explode('/', $requestPath);
        
        // Nombre de segments différent = pas de correspondance
        if (count($routeSegments) !== count($requestSegments)) {
            return false;
        }
        
        $params = [];
        
        for ($i = 0; $i < count($routeSegments); $i++) {
            $routeSegment = $routeSegments[$i];
            $requestSegment = $requestSegments[$i];
            
            // Segment avec paramètre
            if (preg_match('/\{([^}]+)\}/', $routeSegment, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $requestSegment;
            } else {
                // Segment fixe - doit correspondre exactement
                if ($routeSegment !== $requestSegment) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
?>