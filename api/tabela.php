<?php declare(strict_types=1);

require 'cdc.php';


// Aqui vai ter a parte da pagina da tabela price

function getLeftBoxHTMLText(float $precoAVista, float $precoAPrazo, int $numParcelas, float $taxaDeJuros, bool $temEntrada, int $mesesAVoltar): string
{
    $precoAprazoTemp = numberToFixed((float) $precoAPrazo, 2);
    $precoAVistaTemp = numberToFixed((float) $precoAVista, 2);
    $numParcelasTemp = (int) $numParcelas;
    $mesesAVoltarTemp = (int) $mesesAVoltar;
    $taxaDeJurosTemp = numberToFixed((float) $taxaDeJuros, 4);
    $taxaDeJurosAnual = converterJurosMensalParaAnual($taxaDeJuros);

    //    let jurosReal = calcularTaxaDeJuros(precoAVista,precoAPrazo,numParcelas,temEntrada) * 100;
    $coeficienteFinanciamento = calcularCoeficienteFinanciamento($taxaDeJuros, $numParcelas);

    $pmt = numberToFixed(calcularPMT($precoAVista, $coeficienteFinanciamento), 2);
    $valorAVoltar = numberToFixed(calcularValorAVoltar($pmt, $numParcelasTemp, $mesesAVoltarTemp), 2);

    $textoParcelamento = $temEntrada ? " (+ 1)" : "";
    $textoTemEntrada = $temEntrada ? "Sim" : "Não";

    $taxaDeJurosTemp *= 100;
    return "<p><b>Parcelamento:</b> {$numParcelas} {$textoParcelamento} </p>
    <p><b>Taxa:</b> {$taxaDeJurosTemp}% Ao Mês ({$taxaDeJurosAnual}% Ao Ano) </p>
    <p><b>Valor Financiado:</b> $ {$precoAVistaTemp} </p>
    <p><b>Valor Final:</b> $ {$precoAprazoTemp}</p>
    <p><b>Meses a Voltar(Adiantados):</b> {$mesesAVoltar} </p>
    <p><b>Valor a voltar(Adiantamento da dívida):</b> $ {$valorAVoltar} </p>
    <p><b>Entrada:</b> {$textoTemEntrada} </p> ";
}

function getRightBoxHTMLText(float $precoAVista, float $precoAPrazo, int $numParcelas, float $taxaDeJuros, bool $temEntrada, float $valorCorrigido): string
{

    $jurosReal = 0;

    $precoAprazoTemp = numberToFixed((float) $precoAPrazo, 2);
    $precoAVistaTemp = numberToFixed((float) $precoAVista, 2);
    $numParcelasTemp = (int) $numParcelas;

    //numParcelas = (!temEntrada)? numParcelas: numParcelas + 1;

    $jurosReal = calcularTaxaDeJuros($precoAVista, $precoAPrazo, $numParcelas, $temEntrada) * 100;


    $coeficienteFinanciamento = calcularCoeficienteFinanciamento($taxaDeJuros, $numParcelas);

    $jurosReal = numberToFixed($jurosReal, 4);

    $pmt = toFixed(calcularPMT($precoAVista, $coeficienteFinanciamento), 2);

    $jurosEmbutido = (($precoAPrazo - $precoAVista) / $precoAVista) * 100;
    $jurosEmbutido = numberToFixed($jurosEmbutido, 2);
    $desconto = (($precoAPrazo - $precoAVista) / $precoAPrazo) * 100;
    $desconto = numberToFixed($desconto, 2);
    $fatorAplicado = toFixed(calcularFatorAplicado($temEntrada, $numParcelas, $coeficienteFinanciamento, $taxaDeJuros), 6);
    $coeficienteFinanciamento = numberToFixed($coeficienteFinanciamento, 6);
    return "
    <p><b>Prestação:</b> $ {$pmt}</p>
    <p> <b>Taxa Real:</b>  {$jurosReal}%</p>
    <p> <b>Coeficiente de Financiamento:</b> {$coeficienteFinanciamento} </p>
    <p><b>Fator Aplicado:</b> {$fatorAplicado}</p>
    <p> <b>Valor Corrigido:</b> $ {$valorCorrigido} </p>
    <p> <b>Juros Embutido:</b> {$jurosEmbutido}% </p>
    <p> <b>Desconto:</b>  {$desconto}% </p>
    ";
}


function getTabelaPriceHTMLText(array $tabelaPrice): string
{

    $table = "";

    for ($i = 0; $i < count($tabelaPrice); $i++) {

        if ($i == 0) {
            $table .= "<thead><tr>";

            foreach ($tabelaPrice[$i] as $itemTabela) {
                $table .= "<th> {$itemTabela} </th>";
            }


            $table .= "</tr></thead>";
        } else {

            $table .= "<tr>";
            foreach ($tabelaPrice[$i] as $itemTabelaa) {

                // Coloca Negrito se for último elemento (Totais)
                if ($i == count($tabelaPrice) - 1) {
                    $table .= "<td> <b>  $itemTabelaa   </b> </td>";
                } else {
                    $table .= "<td>  $itemTabelaa </td>";
                }

            }


            $table .= "</tr>";
        }

    }


    return $table;
}



function printPage(string $leftBoxContent, string $rightBoxContent, string $tabelaPriceContent): void
{
    $finalText = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>CDC</title>
    <meta charset="utf8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link
        rel="stylesheet"
        href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css"
    />
    <script src="js-webshim/minified/polyfiller.js"></script>
    <style>
        body {
            background-color: #1e1e1e;
            color: #fff;
            margin: 0;
            padding: 2em;
            font-family: -apple-system, system-ui, BlinkMacSystemFont,
                "Segoe UI", "Open Sans", "Helvetica Neue", Helvetica, Arial,
                sans-serif;
        }

        #result-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        #summary-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            margin-top: 20px;
        }

        #left-box,
        #right-box {
            min-width: 40%; 
            border-style: solid;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        #left-box {
            float: left;
            margin-right: 20px;
        }

        #right-box {
            float: left;
        }

        #left-box p,
        #right-box p {
            font-size: 16px;
            color: #fff;
            margin: 5px 0;
        }

        #table-container {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        #table-content {
            border-collapse: collapse;
            width: 80%;
            margin-top: 20px;
        }

        #table-content,
        th,
        td {
            border: 1px solid #555;
            font-size: 16px;
            padding: 10px;
            text-align: center;
        }

        h1 {
            color: #BB86FC;
        }
    </style>
</head>
<body>
    <div id="result-container">
        <div id="summary-container">
            <div id="left-box">
                {$leftBoxContent}
            </div>
            <div id="right-box">
                {$rightBoxContent}
            </div>
        </div>
        <div id="table-container">
            <h1>Tabela Price</h1>
            <table id="table-content">
                {$tabelaPriceContent}
            </table>
        </div>
    </div>
</body>
</html>
HTML;

    echo $finalText;
}



/*
colinha campos:

numero de parcelas: np
taxa de juros: tax
precoAVista: pv
precoAPrazo: pp
Meses à Voltar: pb
Tem entrada?: dp

*/

$numeroParcelas = (int) $_POST["np"];
$juros = (float) $_POST["tax"];
$valorFinanciado = (float) $_POST["pv"];
$valorFinal = (float) $_POST["pp"];
$mesesAVoltar = (int) $_POST["pb"];
$temEntrada = (bool) $_POST["dp"];


$tabelaPrice;
$valorCorrigido;
$coeficienteFinanciamento;
$pmt;


if ($juros != 0 && $valorFinal == 0) {
    $juros /= 100;

} else {
    $juros = calcularTaxaDeJuros($valorFinanciado, $valorFinal, $numeroParcelas, $temEntrada);
}


$coeficienteFinanciamento = calcularCoeficienteFinanciamento($juros, $numeroParcelas);

if ($valorFinal == 0) {
    $valorFinal = calcularValorFuturo($coeficienteFinanciamento, $juros, $valorFinanciado, $numeroParcelas, $temEntrada);
}
$pmt = calcularPMT($valorFinanciado, $coeficienteFinanciamento);

if ($temEntrada) {
    $pmt /= 1 + $juros;
    $numeroParcelas--;
    $valorFinanciado -= $pmt;


}

$tabelaPrice = getTabelaPrice($valorFinanciado, $pmt, $numeroParcelas, $juros, $temEntrada);

$valorCorrigido = getValorCorrigido($tabelaPrice, $numeroParcelas, $mesesAVoltar);

$tabelaPriceText = getTabelaPriceHTMLText($tabelaPrice);

$leftBoxText = getLeftBoxHTMLText($valorFinanciado, $valorFinal, $numeroParcelas, $juros, $temEntrada, $mesesAVoltar);
$rightBoxText = getRightBoxHTMLText($valorFinanciado, $valorFinal, $numeroParcelas, $juros, $temEntrada, $valorCorrigido);



printPage($leftBoxText, $rightBoxText, $tabelaPriceText);

// if(imprimir){
//     imprimirResultado();
// }else{
//     scrollTo("left-box");

// }

?>