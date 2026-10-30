<?php
function e(mixed $v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }

function asset(string $path): string
{
    $file = ROOT_PATH . '/public/' . ltrim($path, '/');
    return url($path) . '?v=' . (is_file($file) ? filemtime($file) : 1);
}

function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function flash(string $type, string $msg): void { $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg]; }
function pull_flash(): array { $f = $_SESSION['_flash'] ?? []; unset($_SESSION['_flash']); return $f; }

function with_old(array $data): void { $_SESSION['_old'] = $data; }
function old(string $key, string $default = ''): string { return e($GLOBALS['_old'][$key] ?? $default); }

function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">'; }

function money(float|int|string|null $n): string { return 'S/ ' . number_format((float) $n, 2); }

function saludo(): string
{
    $h = (int) date('G');
    if ($h >= 5 && $h < 12) return 'Buenos días';
    if ($h >= 12 && $h < 19) return 'Buenas tardes';
    return 'Buenas noches';
}

function fecha_larga(?int $ts = null): string
{
    $ts ??= time();
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return ucfirst($dias[(int) date('w', $ts)]) . ' ' . date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function hora12(string $datetime): string
{
    return str_replace(['AM', 'PM'], ['a. m.', 'p. m.'], date('g:i A', strtotime($datetime)));
}
