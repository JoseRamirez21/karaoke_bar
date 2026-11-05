<?php
/** Utilidades de YouTube que NO necesitan clave: sacar el código del enlace y leer el título. */
class Youtube
{
    /** Saca el código de 11 caracteres de cualquier enlace de YouTube (o null si no es de YouTube). */
    public static function idDeEnlace(string $texto): ?string
    {
        $texto = trim($texto);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $texto)) return $texto;      // pegaron solo el código

        if (!preg_match('#^https?://#i', $texto)) $texto = 'https://' . $texto;
        $p = parse_url($texto);
        if (!$p || empty($p['host'])) return null;

        // Se quita www., m. y music. del inicio; el resto debe ser EXACTAMENTE youtube.com o youtu.be
        $host = strtolower((string) preg_replace('/^(www\.|m\.|music\.)/i', '', $p['host']));
        $id   = null;
        if ($host === 'youtu.be') {
            $id = explode('/', trim($p['path'] ?? '', '/'))[0];
        } elseif ($host === 'youtube.com' || $host === 'youtube-nocookie.com') {
            $path = $p['path'] ?? '';
            if ($path === '/watch') {
                parse_str($p['query'] ?? '', $q);
                $id = is_string($q['v'] ?? null) ? $q['v'] : null;
            } elseif (preg_match('#^/(shorts|embed|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[2];
            }
        }
        return ($id !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
    }

    public static function miniatura(string $id): string
    {
        return 'https://i.ytimg.com/vi/' . $id . '/mqdefault.jpg';
    }

    /**
     * Comprueba que el video exista y se pueda reproducir, y devuelve su título.
     * Si el servidor no tiene internet o YouTube no responde, NO se bloquea al cliente:
     * se acepta con un título genérico.
     * @return array{ok: bool, titulo: string, error: ?string}
     */
    public static function info(string $id): array
    {
        $url = YOUTUBE_OEMBED . '?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $id);
        [$codigo, $cuerpo] = self::http($url);

        if ($codigo === 200) {
            $d = json_decode($cuerpo, true);
            $t = is_array($d) ? trim((string) ($d['title'] ?? '')) : '';
            return ['ok' => true, 'titulo' => self::limpiar($t !== '' ? $t : 'Video de YouTube'), 'error' => null];
        }
        if ($codigo === 404 || $codigo === 400) {
            return ['ok' => false, 'titulo' => '', 'error' => 'No encontramos ese video. Revisa el enlace.'];
        }
        if ($codigo === 401 || $codigo === 403) {
            return ['ok' => false, 'titulo' => '', 'error' => 'Ese video es privado o no se puede reproducir fuera de YouTube. Elige otro.'];
        }
        return ['ok' => true, 'titulo' => 'Video de YouTube (' . $id . ')', 'error' => null];
    }

    private static function limpiar(string $t): string
    {
        $t = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $t) ?? $t;
        return mb_substr(trim($t), 0, 200);
    }

    /** @return array{0:int,1:string} [código HTTP (0 si no hubo respuesta), cuerpo] */
    private static function http(string $url): array
    {
        if (function_exists('curl_init')) {
            $hacer = function (bool $verificar) use ($url): array {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'KaraokeBar/1.0',
                    CURLOPT_SSL_VERIFYPEER => $verificar, CURLOPT_SSL_VERIFYHOST => $verificar ? 2 : 0,
                ]);
                $cuerpo = curl_exec($ch);
                $errno  = curl_errno($ch);
                $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                return [$cuerpo === false ? 0 : $codigo, (string) $cuerpo, $errno];
            };
            [$codigo, $cuerpo, $errno] = $hacer(true);
            // XAMPP en Windows a veces no trae los certificados y falla la verificación SSL (errores 35, 51, 58, 60, 77).
            // Solo se lee un dato público (el título), así que se reintenta sin verificar en vez de bloquear la función.
            if ($codigo === 0 && in_array($errno, [35, 51, 58, 60, 77], true)) {
                [$codigo, $cuerpo] = $hacer(false);
            }
            return [$codigo, $cuerpo];
        }
        $ctx    = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => "User-Agent: KaraokeBar/1.0\r\n"]]);
        $cuerpo = @file_get_contents($url, false, $ctx);
        $codigo = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) $codigo = (int) $m[1];
        return [$cuerpo === false ? 0 : $codigo, (string) $cuerpo];
    }
}