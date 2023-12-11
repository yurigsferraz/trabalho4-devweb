<?php

/**
 * Summary.
 * Desconto Racional por Dentro.
 *
 * Coded following CommonJS module syntax,
 * which was the original way to package JavaScript code for Node.js.
 *
 * In fact, since this script is meant to be run on the server, via nodejs (and not on the browser),
 * it must export its functions to serve Express,
 * which provides a robust set of features for web and mobile applications.
 *
 * @author Paulo Roma
 * @since 24/10/2023
 * @see source
 * @see https://cdc-express.vercel.app
 * @see https://www.uel.br/projetos/matessencial/basico/financeira/curso.html
 * @see https://calculador.com.br/calculo/financiamento-price
 * @see https://dicascarrosusados.com/financiar-carro-por-cdc/
 * @see https://mundoeducacao.uol.com.br/matematica/calculo-financiamento.htm
 * @see https://edisciplinas.usp.br/pluginfile.php/4647782/mod_resource/content/1/ENS - MTF 191S - Aula 07 C Financiamentos.pdf
 * @see Avaliação Financeira
 */

/**
 * @var mixed result <div> element.
 */
$result = "";

/**
 * @var callable log output to node or browser.
 */
$log = (isset($GLOBALS['process']) && is_object($GLOBALS['process'])) ? 'appendToDiv' : 'appendToDiv';

/**
 * Appends a string to a div with a given id.
 * @param string $str a string.
 * @param string $id given id.
 */
function appendToDiv($str, $id = "#rational")
{
    global $result;
    global $crlf;

    if (is_string($result)) {
        $result = document.querySelector($id);
        $result .= $str . $crlf;
    } else {
        $result .= $str . $crlf;
    }
}

/**
 * Appends a string to another (result) string.
 * @param string $str a string.
 */
function appendToString($str)
{
    global $result;
    $result = $result . $str . $crlf;
}

/**
 * @property array pt - data for formatting a table.
 * @property int pt.lenNum - length of a float number.
 * @property int pt.lenMes - length of an integer.
 * @property int pt.precision - number of decimal places.
 * @property string pt.eol - column separator.
 * @property string pt.filler - character for padding.
 */
$pt = [
    'lenNum' => 13,
    'lenMes' => 6,
    'precision' => 2,
    'eol' => "|",
    'filler' => " ",
];

/**
 * @var string crlf newline.
 */
$crlf = "<br>";

/**
 * Create and initialize the "static" variable.
 * Function declarations are processed before code is executed, so
 * we really can do this assignment before the function declaration.
 */
$downPayment = false;
/**
 * Seleciona pagamento mensal com ou sem entrada (down payment).
 * Holds a "static" property of itself to keep track
 * of the last value set.
 * @param bool $dp down payment.
 * @property bool downP whether there is a down payment or not.
 */
function setDownPayment($dp = true)
{
    global $setDownPayment;
    $setDownPayment['downP'] = $dp;
}

/**
 * Retorna o tipo de pagamento mensal (down payment).
 * @return bool whether there is a down payment.
 */
function getDownPayment()
{
    global $setDownPayment;
    return $setDownPayment['downP'];
}

/**
 * Checks whether a number is close to zero given a tolerance interval.
 * @param float $n number.
 * @param float $tol tolerance.
 * @return bool whether n is close to zero.
 */
function isZero($n, $tol = 1.0e-8)
{
    return abs($n) < $tol;
}

/**
 * Testing for -0 in JavaScript.
 * @param float $x a number.
 * @return bool true if -0, and false otherwise.
 */
function isNegative0($x)
{
    if ($x !== 0) return false;
    $obj = (object) ['z' => -0];
    try {
        $obj->z = $x;
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Acha a taxa que produz o preço à vista, com ou sem entrada,
 * pelo método de Newton:
 *
 * t_{n+1} = t_n - f(t_n) / f'(t_n)
 *
 * A função é decrescente e converge para t = lim_{n→∞} t_{n+1}.
 *
 * Nota: se não houve entrada, retorna getInterest2(x, y, p)
 *
 * @param float $x preço a prazo.
 * @param float $y preço à vista.
 * @param int $p número de parcelas.
 * @return array [taxa, número de iterações].
 */
function getInterest($x, $y, $p)
{
    if ($x == 0 || $y == 0 || $p == 0) return [0, 0];
    $R = $x / $p; // prestação

    if (!getDownPayment()) {
        return getInterest2($x, $y, $p);
    } else {
        $t2 = $x / $y;
        $t = 0;
        $n = 0;
        while (!isZero($t2 - $t)) {
            if ($n > 150) throw new Exception("Newton is not converging!");
            $t = $t2;
            $n += 1;
            $tPlusOne = 1.0 + $t;
            $a = pow($tPlusOne, -$p); // (1.0+t)**(-p)
            $d = $y * $t - $R * (1 - $a) * $tPlusOne; // f(t_n)
            $dt = $y - $R * (1 - $a * (1 - $p)); // f'(t_n)
            $t2 = $t - $d / $dt;
        }
        if (isZero($t2, 1.0e-10)) throw new Exception("Newton did not converge!");

        return [$t2 * 100.0, $n];
    }
}

/**
 * Acha a taxa que produz o preço à vista, sem entrada,
 * pelo método de Newton:
 *
 * t_{n+1} = t_n - f(t_n) / f'(t_n)
 *
 * A função é decrescente e converge para t = lim_{n→∞} t_{n+1}.
 *
 * Nota: assume-se que não houve entrada.
 *
 * @param float $x preço a prazo.
 * @param float $y preço à vista.
 * @param int $p número de parcelas.
 * @return array [taxa, número de iterações].
 */
function getInterest2($x, $y, $p)
{
    if ($x == 0 || $y == 0 || $p == 0) return [0, 0];
    $t2 = $x / $y;
    $t = 0;
    $n = 0;
    while (!isZero($t2 - $t)) {
        if ($n > 150) throw new Exception("Newton is not converging!");
        $t = $t2;
        $n += 1;
        $tPlusOne = 1.0 + $t;
        $a = pow($tPlusOne, -$p); // (1.0+t)**(-p)
        $b = $a / $tPlusOne; // (1.0+t)**(-p-1)
        $d = $y * $t - ($x / $p) * (1 - $a); // f(t_n)
        $dt = $y - $x * $b; // f'(t_n)
        $t2 = $t - $d / $dt;
    }
    if (isZero($t2, 1.0e-10)) throw new Exception("Newton did not converge!");
    return [$t2 * 100.0, $n];
}

/**
 * Retorna o fator para atualizar o preço e o valor no instante da compra.
 *
 * @param float $x preço a prazo.
 * @param int $p número de parcelas.
 * @param float $t taxa.
 * @param bool $fix whether to take into account a down payment.
 * @return array [factor, x * factor]
 */
function presentValue($x, $p, $t, $fix = true)
{
    $factor = 1.0 / ($p * CF($t, $p));
    if ($fix && getDownPayment()) {
        $factor *= 1.0 + $t;
    }
    return [$factor, $x * $factor];
}

/**
 * Retorna o fator para atualizar o preço e o valor ao final do pagamento.
 *
 * @param float $y preço à vista.
 * @param int $p número de parcelas.
 * @param float $t taxa.
 * @param bool $fix whether to take into account a down payment.
 * @return array [factor, y * factor]
 */
function futureValue($y, $p, $t, $fix = true)
{
    $factor = CF($t, $p) * $p;
    if ($fix && getDownPayment()) {
        $factor /= 1.0 + $t;
    }
    return [$factor, $y * $factor];
}

/**
 * Coeficiente de financiamento CDC (Crédito Direto ao Consumidor).
 * É o fator financeiro constante que, ao multiplicar-se pelo valor presente
 * de um financiamento, apura o valor das prestações:
 *
 * R = CF * val
 *
 * Assim, ele indica o valor da prestação que deve ser paga por cada unidade monetária
 * que está sendo tomada emprestada.
 * Por exemplo, se o coeficiente for igual a 0,05, então o tomador de recursos
 * deve pagar $0,05 de prestação para cada $1,00 de dívida.
 *
 * @param float $i taxa mensal.
 * @param int $n período (meses).
 * @return float coeficiente de financiamento.
 */
function CF($i, $n)
{
    return $i / (1 - pow(1 + $i, -$n));
}

/**
 * Desconto Racional por Dentro.
 *
 * @param int $p número de prestações.
 * @param float $t taxa de juros mensal.
 * @param float $x preço a prazo.
 * @param float $y preço à vista.
 * @param bool $option seleciona o que será impresso.
 * @return string answer as a raw string or in HTML format.
 */
function rational_discount($p, $t, $x, $y, $option = true)
{
    global $result;
    global $log;
    global $crlf;

    $result = "";
    if ($y >= $x) {
        $log("Preço à vista deve ser menor do que o preço total:");
    } else {
        list($interest, $niter) = getInterest($x, $y, $p);
        if ($t == 0) {
            $t = 0.01 * $interest;
        }

        list($fx, $ux) = presentValue($x, $p, $t);
        if ($y <= 0) {
            $y = $ux;
        }
        list($fy, $uy) = futureValue($y, $p, $t);
        if (isZero($y - $ux, 0.01)) {
            $log("O preço à vista é igual ao preço total corrigido.");
        } elseif ($y > $ux) {
            $log(
                "O preço à vista é maior do que preço total corrigido ⇒ melhor comprar a prazo."
            );
        } else {
            $log("O preço à vista é menor ou igual do que preço total corrigido.");
        }
        $delta_p = $ux - $y;
        if (isZero($delta_p)) $delta_p = 0;
        $prct = ($delta_p / $ux) * 100.0;

        $log(
            "Taxa Real = " . number_format($interest, 4) . "%, Iterações = " . $niter . ", Fator = " . number_format($fx, 4)
        );
        $log(
            "Preço à vista + juros de " . number_format($t * 100, 2) . "% ao mês = \$" . number_format($uy, 2)
        );
        $log(
            "Preço a prazo - juros de " . number_format($t * 100, 2) . "% ao mês = \$" . number_format($ux, 2)
        );
        $log(
            "Juros Embutidos = (\$" . number_format($x, 2) . " - \$" . number_format($y, 2) . ") / \$" . number_format($y, 2) . " = " . number_format((($x - $y) / $y) * 100, 2) . "%"
        );
        $log(
            "Desconto = (\$" . number_format($x, 2) . " - \$" . number_format($y, 2) . ") / \$" . number_format($x, 2) . " = " . number_format((($x - $y) / $x) * 100, 2) . "%"
        );
        $log(
            "Excesso = \$" . number_format($ux, 2) . " - \$" . number_format($y, 2) . " = \$" . number_format($delta_p, 2)
        );
        $log(
            "Excesso = (\$" . number_format($x, 2) . " - \$" . number_format($uy, 2) . ") * " . number_format($fx, 4) . " = \$" . number_format(($x - $uy) * $fx, 2)
        );
        $log("Percentual pago a mais = " . number_format($prct, 2) . "%");
        if ($option) {
            if (0.0 <= $prct && $prct <= 1.0) {
                $log("Valor aceitável.");
            } elseif (1.0 < $prct && $prct <= 3.0) {
                $log("O preço está caro.");
            } elseif (3.0 < $prct) {
                $log("Você está sendo roubado.");
            }
        }

        $cf = CF($t, $p);
        $pmt = $y * $cf;
        if (getDownPayment()) {
            $pmt /= 1 + $t;
            $p -= 1; // uma prestação a menos
            $y -= $pmt; // preço à vista menos a entrada
            $cf = $pmt / $y; // recalculate cf
        }
        $ptb = priceTable($p, $y, $t, $pmt);
        $log($crlf);
        $log(nodePriceTable($ptb));
    }
    return $result;
}

/**
 * Center a string in a given length.
 *
 * @param string $str string.
 * @param int $len length.
 * @return string a string padded with spaces to fit in the given length.
 */
function center($str, $len)
{
    global $pt;
    return str_pad(
        str_pad($str, strlen($str) + floor(($len - strlen($str)) / 2), $pt['filler']),
        $len,
        $pt['filler']
    );
}

/**
 * Retorna a Tabela Price, também chamada de sistema francês de amortização.
 *
 * É um método usado em amortização de empréstimos cuja principal característica
 * é apresentar prestações (ou parcelas) iguais,
 * "escamoteando os juros".
 *
 * O método foi apresentado em 1771 por Richard Price em sua obra
 * "Observações sobre Pagamentos Remissivos".
 *
 * Os valores das parcelas podem ser antecipados,
 * calculando-se o desconto correspondente.
 *
 * O saldo devedor é sempre o valor a ser pago, quando se quitar a dívida num mês determinado.
 * Esse é o tal "desconto racional", quando se antecipam parcelas.
 *
 * @param int $np número de prestações.
 * @param float $pv valor do empréstimo.
 * @param float $t taxa de juros.
 * @param float $pmt pagamento mensal.
 * @return array uma matriz cujas linhas são arrays com:
 *   [Mês, Prestação, Juros, Amortização, Saldo Devedor].
 */
function priceTable($np, $pv, $t, $pmt)
{
    global $pt;
    $dataTable = [
        ["Mês", "Prestação", "Juros", "Amortização", "Saldo Devedor"],
    ];
    $pt = getDownPayment() ? $pmt : 0;
    $jt = 0;
    $at = 0;
    $dataTable[] = ["n", "R = pmt", "J = SD * t", "U = pmt - J", "SD = PV - U"];
    $dataTable[] = [0, $pt, "(" . number_format($t, 4) . ")", 0, $pv];
    if ($t <= 0) return $dataTable;
    for ($i = 0; $i < $np; ++$i) {
        $juros = $pv * $t;
        $amortizacao = $pmt - $juros;
        $saldo = $pv - $amortizacao;
        $pv = $saldo;
        $pt += $pmt;
        $jt += $juros;
        $at += $amortizacao;
        $dataTable[] = [$i + 1, $pmt, $juros, $amortizacao, isZero($saldo) ? 0 : $saldo];
    }
    $dataTable[] = ["Total", $pt, $jt, $at, 0];
    return $dataTable;
}

/**
 * Formats a single row of the node Price Table.
 *
 * @param array $r given row.
 * @return string row formatted.
 */
function formatRow($r)
{
    global $pt;
    $row = "";
    $val;

    foreach ($r as $index => $col) {
        if ($index == 0) {
            $val = center($col, $pt['lenMes']);
            $row .= "{$pt['eol']}{$val}{$pt['eol']}";
        } elseif (is_numeric($col)) {
            $val = number_format($col, $pt['precision']);
            $row .= center($val, $pt['lenNum']) . $pt['eol'];
        } else {
            $row .= center($col, $pt['lenNum']) . $pt['eol'];
        }
    }
    return $row;
}

/**
 * Return the Price Table in node format using characters only.
 * @param array $ptb price table.
 * @return string price table.
 */
function nodePriceTable($ptb)
{
    global $pt, $crlf;
    // Number of float columns
    $nfloat = count($ptb[0]) - 1;
    // Length of a row.
    $lenTable = $pt['lenMes'] + ($pt['lenNum'] + strlen($pt['eol'])) * $nfloat;

    // Line separator.
    $line = "{$pt['eol']}" . str_repeat("_", $pt['lenMes']) . "{$pt['eol']}" . (
        str_repeat("_", $pt['lenNum']) . $pt['eol']
    ) * $nfloat;
    $line2 = " " . str_repeat("_", $lenTable);

    $table = [];

    $table[] = center("Tabela Price", $lenTable);
    $table[] = $line2;
    foreach ($ptb as $index => $row) {
        $table[] = formatRow($row);
        if ($index == 0 || $index == count($ptb) - 2) {
            $table[] = $line;
        }
    }
    $table[] = $line;

    return implode($crlf, $table);
}

/**
 * Returns the Price Table in HTML format.
 *
 * @param array $ptb Price table.
 * @return string Price table in html format.
 */
function htmlPriceTable($ptb)
{
    $table = "<table border=1>
      <caption style='font-weight: bold; font-size:200%;'>
        Tabela Price
      </caption>
      <tbody style='text-align:center;'>
    ";
    foreach ($ptb as $i => $row) {
        $table .= "<tr>";
        foreach ($row as $j => $col) {
            if (is_numeric($col)) {
                if ($j > 0) {
                    $col = number_format($col, $j == 2 ? pt.precision + 1 : pt.precision);
                }
            }
            $table .= $i > 0 ? "<td>{$col}</td>" : "<th>{$col}</th>";
        }
        $table .= "</tr>";
    }
    $table .= "</tbody></table>";

    return $table;
}

/**
 * Command Line Interface for CDC.
 *
 * Command Line Arguments:
 * - h help
 * - n número de parcelas.
 * - t taxa mensal.
 * - x valor da compra a prazo.
 * - y valor da compra à vista.
 * - e indica uma entrada.
 * - v verbose mode
 *
 * Module requirements:
 * - composer require aaronfrederick/posix-getopt
 * - composer require symfony/console
 *
 * Usage:
 * - php rational.php -n10 -t1 -x500 -y450 -e
 * - php rational.php -n18 -t0 -x3297.60 -y1999
 * - php rational.php -n10 -t0 -x1190 -y1094.80
 * - php rational.php -n 88 -t 4.55 -x 111064.80 -y 23000
 * - php rational.php -n 96 -t 0 -x 134788.8 -y 63816.24
 * - php rational.php -n 4 -t 3.0 -x 1076.11  -y 1000
 * - php rational.php --parcelas=88 --taxa=4.55 --valorP=111064.80 --valorV=23000 -v
 * - php rational.php --help
 *
 * @param array $argv command line arguments.
 *
 * @see https://github.com/aaronfrederick/posix-getopt
 * @see https://symfony.com/doc/current/components/console.html
 */
function cdcCLI($argv = [])
{
    // number of payments.
    $np = 0;
    // interest rate
    $t = 0;
    // initial price
    $pv = 0;
    // final price
    $pp = 0;
    // debugging state.
    $debug = false;
    // holds the existence of a down payment.
    setDownPayment(false);

    $pt = [
        'precision' => 4,
    ];

    $mod_getopt = require __DIR__ . '/vendor/autoload.php';
    $console = new Symfony\Component\Console\Application();
    $console->run();
    $parser = new $mod_getopt\Getopt(
        [
            ['h', 'help', $mod_getopt\Getopt::NO_ARGUMENT],
            ['n', 'parcelas', $mod_getopt\Getopt::REQUIRED_ARGUMENT],
            ['t', 'taxa', $mod_getopt\Getopt::REQUIRED_ARGUMENT],
            ['x', 'valorP', $mod_getopt\Getopt::REQUIRED_ARGUMENT],
            ['y', 'valorV', $mod_getopt\Getopt::REQUIRED_ARGUMENT],
            ['v', 'verbose', $mod_getopt\Getopt::NO_ARGUMENT],
            ['e', 'entrada', $mod_getopt\Getopt::NO_ARGUMENT],
        ],
        $argv
    );

    foreach ($parser->getOptions() as $option) {
        switch ($option[0]) {
            case 'h':
                log(
                    "Usage " . parse($argv[0]) . " " . parse(
                        $argv[1]
                    ) . " -n <nº parcelas> -t <taxa> -x <valor a prazo> -y <valor à vista> -e -v"
                );
                return 1;
            case 'n':
                $np = (int)$option[1];
                break;
            case 't':
                $t = (float)$option[1] / 100.0;
                break;
            case 'x':
                $pp = (float)$option[1];
                break;
            case 'y':
                $pv = (float)$option[1];
                break;
            case 'v':
                $debug = true;
                break;
            case 'e':
                setDownPayment();
                break;
        }
    }

    while (
        $np <= 2 ||
        ($pv <= 0 && $pp <= 0) ||
        ($t <= 0 && $pp <= 0) ||
        ($t <= 0 && $pv <= 0) ||
        $pp < $pv
    ) {
        try {
            $np = (int)readlineSync::question("Forneça o número de parcelas: ");
            $t = (float)readlineSync::question("Forneça a taxa de juros: ") / 100.0;
            $pp = (float)readlineSync::question("Forneça o preço a prazo: ");
            $pv = (float)readlineSync::question("Forneça o preço à vista: ");
            if (isNaN($np) || isNaN($t) || isNaN($pp) || isNaN($pv)) {
                throw new Exception("Value is not a Number");
            }
        } catch (Exception $err) {
            log($err->getMessage());
            rational_discount(10, 0.01, 500, 450, $debug);
            return;
        }
    }

    if ($t > 0) {
        if ($pp <= 0) {
            [$factor, $pp] = futureValue($pv, $np, $t);
        }
    } else {
        $ni = 0;
        $pmt = $pp / $np;
        try {
            if ($pmt >= $pv) {
                throw new Exception(
                    "Prestação (\$" . $pmt->toFixed(2) . ") é maior do que o empréstimo"
                );
            }
            // getInterest takes in considerarion any down payment
            [$t, $ni] = getInterest($pp, $pv, $np);
        } catch (Exception $e) {
            log($e->getMessage());
            return;
        }
        log("Taxa = " . $t->toFixed(4) . "% - " . $ni . " iterações" . crlf);
        $t *= 0.01;
    }

    // with or without any down payment
    $cf = CF($t, $np);
    $pmt = $pv * $cf;
    if ($pmt >= $pv) {
        rational.log(
            "Prestação (\$" . $pmt->toFixed(2) . ") é maior do que o empréstimo"
        );
    }
    log("Coeficiente de Financiamento: " . $cf->toFixed(6));

    $dp = getDownPayment();
    if ($dp) {
        $pmt /= 1 + $t;
        $np -= 1; // uma prestação a menos
        $pv -= $pmt; // preço à vista menos a entrada
        $pp -= $pmt; // preço a prazo menos a entrada
        log("Entrada: " . $pmt->toFixed(2));
        log(
            "Valor financiado = \$" . ($pv + $pmt)->toFixed(2) . " - \$" . $pmt->toFixed(
                2
            ) . " = \$" . $pv->toFixed(2)
        );
        // the values were set here to work without down payment
        // otherwise, rational_discount will produce a misleading interest rate
        setDownPayment(false);
    }

    log("Prestação: \$" . $pmt->toFixed(2) . crlf);

    $output = $result->slice() . rational_discount($np, $t, $pp, $pv, $debug);
    $result = "";
    $output = $output->slice(
        0,
        $output->indexOf(crlf . "                         Tabela Price")
    );

    // Tabela Price
    if ($debug) {
        setDownPayment($dp);
        log(nodePriceTable(priceTable($np, $pv, $t, $pmt)));
        $output .= $result;
    }
    echo $output->split(crlf)->join("\n");
}

?>