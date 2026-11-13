<?php
class Router
{
    public static string $controller = '';

    public function dispatch(): void
    {
        try {
            $url   = trim((string) ($_GET['url'] ?? ''), '/');
            $parts = $url === '' ? [] : explode('/', $url);

            if (!$parts) {
                redirect(Auth::check() ? 'dashboard' : 'auth/login');
            }

            $c = strtolower($parts[0]);
            $m = $parts[1] ?? ($c === 'auth' ? 'login' : 'index');
            $params = array_slice($parts, 2);

            if (!preg_match('/^[a-z][a-z0-9]*$/', $c) || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $m)) {
                (new ErrorController())->show404();
                return;
            }

            $class = ucfirst($c) . 'Controller';
            if (!is_file(APP_PATH . "/controllers/$class.php") || !class_exists($class)) {
                (new ErrorController())->show404();
                return;
            }

            self::$controller = $c;
            $obj = new $class();
            if (!method_exists($obj, $m)) {
                (new ErrorController())->show404();
                return;
            }
            $ref = new ReflectionMethod($obj, $m);
            if (!$ref->isPublic() || $ref->isConstructor() || $ref->getDeclaringClass()->getName() === 'Controller') {
                (new ErrorController())->show404();
                return;
            }
            if (count($params) > $ref->getNumberOfParameters()) {
                (new ErrorController())->show404();
                return;
            }

            $ref->invokeArgs($obj, $params);
        } catch (AppException $e) {
            (new ErrorController())->show500($e->getMessage());
        } catch (Throwable $e) {
            error_log((string) $e);
            (new ErrorController())->show500(APP_ENV === 'development' ? $e->getMessage() : null);
        }
    }
}
