<?php
/**
 * CDC router.
 * @see https://vercel.com/guides/using-express-with-vercel Vercel
 */

 require_once(__DIR__ . '/../public/rational.php');

// Criação do objeto de roteamento
$router = new Router();

// Middleware para lidar com POST
$router->use(function ($request, $response, $next) {
    $request->body = json_decode(file_get_contents('php://input'), true);
    $next();
});

/**
 * Middleware para lidar com POST e GET
 */
function handleRequest($request, $response) {
    $arr = [
        +$request->body['np'],
        +$request->body['tax'],
        +$request->body['pv'],
        +$request->body['pp'],
        +$request->body['pb'],
        +$request->body['nb'],
    ];

    $dp = isset($request->body['dp']);
    $prt = isset($request->body['pdf']);

    $response->send(createHTML($arr, $prt));
}

// Rota para POST /api
$router->post('/api', function ($request, $response) {
    handleRequest($request, $response);
});

// Rota para GET /api
$router->get('/api', function ($request, $response) {
    handleRequest($request, $response);
});

// Rota para GET /api/cgi
$router->get('/api/cgi', function ($request, $response) {
    $arr = [+$_GET['np'], +$_GET['tax'], +$_GET['pv'], +$_GET['pp']];
    [$np, $t, $pv, $pp] = $arr;
    $dp = isset($_GET['dp']);
    $prt = isset($_GET['pdf']);
    setDownPayment($dp);
    $result = rational_discount($np, $t * 0.01, $pp, $pv, true);

    $response->send('<html>
    <head>
        <title>CDC - Crédito Direto ao Consumidor (PHP)</title>
        <link rel="stylesheet" href="/cd.css">
        <style>
            body {
                background-color: #f0f0f2;
                background-image: url("/IMAGEM/stone/yell_roc.jpg");
                margin: 0;
                padding: 1em;
            }
        </style>
    </head>
    <body>
      <div id="redBox" class="rectangle">
        <pre>
        <code>
          <p>' . $result . '</p>
        </code>
        </pre>
      </div>
      <script>
        ' . ($prt ? 'print()' : '') . ';
      </script>
    </body>
    </html>');
});

// Rota para GET /api/cdc
$router->all('/api/cdc', function ($request, $response) {
    $response->sendFile('public/cdc.html');
});

// Rota para GET /api/favicon
$router->get('/api/favicon.ico', function ($request, $response) {
    $response->sendFile('public/favicon.ico');
});

// Rota para GET /api/cd.css
$router->get('/api/cd.css', function ($request, $response) {
    $response->sendFile('public/cd.css');
});

// Executa o roteador
$router->run();
