<?php
// --- AJUSTA ESTOS DATOS ---
$URL  = "https://tudireccionweb.com/sitemap_index.xml";
$TO   = "hola@tudireccionweb.com";
$FROM = "monitorsitemap@tudireccionweb.com";
// ---------------------------

$code = 0;
$err  = "";

if (function_exists('curl_init')) {
    $ch = curl_init($URL);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,     // solo cabeceras
        CURLOPT_FOLLOWLOCATION => true,     // seguir 301/302
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        // Si tienes problemas de CA, descomenta las 2 siguientes (no recomendado en prod):
        // CURLOPT_SSL_VERIFYPEER => false,
        // CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_errno($ch) ? curl_error($ch) : "";
    curl_close($ch);
} else {
    // Fallback sin cURL: lanza GET ligera y mira código
    $ctx = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 10, 'ignore_errors' => true]
    ]);
    $data = @file_get_contents($URL, false, $ctx);
    if (isset($http_response_header[0]) &&
        preg_match('~HTTP/\S+\s+(\d{3})~', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    } else {
        $code = 0;
        $err  = "sin_respuesta";
    }
}

if ($code !== 200) {
    $subject = "ALERTA: el mapa del sitio responde $code";
    $body    = "La URL $URL devolvió $code".($err ? " ($err)" : "")
             . " a las ".date('Y-m-d H:i:s');
    $headers = "From: $FROM\r\n";
    @mail($TO, $subject, $body, $headers);
    // Marca error para logs si se ejecuta vía CLI o URL
    http_response_code(500);
    exit(1);
}

// Opcional: salida silenciosa en OK
exit(0);
