<?php
/**
 * Summary.
 * <p><a href="../PDFs/refman.pdf#page=7">Desconto Racional por Dentro</a> versão PHP.</p>
 *
 * Usage:
 * <ul>
 *  <li>Configure o ambiente PHP</li>
 *  <li>https://cdc-php.example.com</li>
 * </ul>
 *
 * @requires routes/routes.php
 *
 * @author Paulo Roma
 * @since 01/11/2023
 * @see <a href="../api/index.php">source</a>
 * @see <a href="../composer.json">composer.json</a>
 * @see <a href="https://cdc-php.example.com">link</a>
 */

$vercel = true;

// Importa o arquivo de rotas
require_once(__DIR__ . '/../routes/routes.php');

// Configura o ambiente
$port = $_SERVER['PORT'] ?? 3000;
$root = '/api';

// Configura o CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Define o caminho para os arquivos estáticos
$publicPath = 'public';

// Configura o favicon (não usado com vercel)
if (!$vercel) {
    $faviconPath = $publicPath . '/favicon.ico';
    if (file_exists($faviconPath)) {
        header('Content-Type: image/x-icon');
        readfile($faviconPath);
        exit;
    }
}

// Middleware
if (!$vercel) {
    $timeElapsed = time();
    $today = date('Y-m-d H:i:s', $timeElapsed);
    echo "Time: $today\n";
    echo "{$_SERVER['REQUEST_METHOD']}: url: {$_SERVER['REQUEST_URI']}, path: {$_SERVER['PATH_INFO']}\n";
    echo $_SERVER['HTTP_REFERER'] ?? '';
}

// Configura as rotas
if (strpos($_SERVER['REQUEST_URI'], $root) === 0) {
    // Remove a parte do URL correspondente ao root
    $routePath = substr($_SERVER['REQUEST_URI'], strlen($root));
    // Chama a função de roteamento correspondente
    handleRoute($routePath);
}

// Não usar com vercel
if (!$vercel) {
    echo "Listening on port $port";
}
