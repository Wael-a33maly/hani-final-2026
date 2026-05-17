<?php
/**
 * app/Core/Router.php - نظام التوجيه البسيط
 */
class Router {
    private $routes = [];

    public function get($uri, $action) {
        $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action) {
        $this->addRoute('POST', $uri, $action);
    }

    private function addRoute($method, $uri, $action) {
        $uri = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $uri);
        $this->routes[] = [
            'method'  => $method,
            'pattern' => '#^' . $uri . '$#',
            'action'  => $action
        ];
    }

    public function dispatch($requestUri) {
        // إزالة query string
        $requestUri = parse_url($requestUri, PHP_URL_PATH);
        
        // حساب الـ Base Path بشكل أدق
        $scriptName = $_SERVER['SCRIPT_NAME']; // مثلاً /index.php أو /hani/index.php
        $baseDir = dirname($scriptName); // مثلاً / أو /hani
        $baseDir = ($baseDir === '\\' || $baseDir === '/') ? '' : $baseDir;

        // إزالة الـ Base Path من الرابط المطلوب
        if (!empty($baseDir) && strpos($requestUri, $baseDir) === 0) {
            $requestUri = substr($requestUri, strlen($baseDir));
        }

        // إزالة الشرطة المائلة الزائدة في النهاية (إلا إذا كان الرابط هو الرئيسي)
        $requestUri = ($requestUri !== '/') ? rtrim($requestUri, '/') : '/';
        $requestUri = empty($requestUri) ? '/' : $requestUri;

        error_log("Routing Request: [Method: " . $_SERVER['REQUEST_METHOD'] . "] [Original: " . $_SERVER['REQUEST_URI'] . "] [Processed: $requestUri]");

        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $requestUri, $matches)) {
                // استخراج المعاملات
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) $params[$key] = $value;
                }

                // استدعاء الـ Controller
                $action = $route['action'];
                list($controllerName, $methodName) = explode('@', $action);
                $controllerFile = CONTROLLERS_PATH . $controllerName . '.php';

                if (!file_exists($controllerFile)) {
                    http_response_code(500);
                    if (file_exists(VIEWS_PATH . 'errors/500.php')) {
                        require_once VIEWS_PATH . 'errors/500.php';
                    } else {
                        die("Controller '$controllerName' not found");
                    }
                    exit;
                }

                require_once $controllerFile;
                $controller = new $controllerName();

                if (!method_exists($controller, $methodName)) {
                    die("Method '$methodName' not found in controller '$controllerName'");
                }

                call_user_func_array([$controller, $methodName], array_values($params));
                return;
            }
        }

        // 404
        http_response_code(404);
        if (file_exists(VIEWS_PATH . 'errors/404.php')) {
            require_once VIEWS_PATH . 'errors/404.php';
        } else {
            echo "404 - Page not found";
        }
        exit;
    }
}
