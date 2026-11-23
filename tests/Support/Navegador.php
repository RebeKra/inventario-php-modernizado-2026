<?php

namespace Tests\Support;

/**
 * Cliente HTTP mínimo que se comporta como un navegador:
 * guarda las cookies de sesión y no sigue las redirecciones,
 * para poder verificar a dónde envía el sistema al usuario.
 */
final class Navegador
{
    private string $cookieFile;

    public function __construct()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'cookies');
    }

    public function __destruct()
    {
        @unlink($this->cookieFile);
    }

    public function get(string $path): Respuesta
    {
        return $this->request('GET', $path);
    }

    /** @param array<string, scalar> $data */
    public function post(string $path, array $data): Respuesta
    {
        return $this->request('POST', $path, $data);
    }

    public function iniciarSesion(string $usuario, string $clave): Respuesta
    {
        return $this->post('/auth.php', ['username' => $usuario, 'password' => $clave]);
    }

    /** @param array<string, scalar> $data */
    private function request(string $method, string $path, array $data = []): Respuesta
    {
        $ch = curl_init(TestServer::baseUrl() . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_PROXY => '',
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($raw, 0, $headerSize);
        $location = preg_match('/^Location:\s*(.+)$/mi', $headers, $m) ? trim($m[1]) : null;

        return new Respuesta($status, $location, substr($raw, $headerSize));
    }
}
