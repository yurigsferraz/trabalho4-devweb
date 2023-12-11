<?php declare(strict_types=1);

/*
Sumario do Pdf:

v = valor de empréstimo
x = preço à prazo
y = preço à vista
p = número de prestações
R = valor de cada prestação
A = Valor presente = valor a vista [fator *= 1 + t][fator, x * fator]
valor  futuro = x(inverso / dividir  (fator =/ 1+ t))CF = Coeficiente de financiamento
t = taxa(porcentagem)
k = fator aplicado


Valor a voltar: Adiantamento(pagar) 

Valor final: Soma das parcelas


Multiplicar juros por saldo devedor pra ter o primo juros
    -> Juros[0] = juros do Usuario
    -> Os demais = Juros * saldo
     
Se tem entrada, primeira prestacao = 0; amortização = 0; juros = juros; amortização = preco a vista, 

Se tiver taxa a juros e valor a vista -> Calular o valor a prazo
Se tiver valor a vista e a prazo -> Calcular taxa de juros commetodo de newton


// ao usar navbar escuro, colocar navbar-dark dentro da classe e style = "background:<cor>"


// CF * y = x

*/

// desconto racional por dentro | ou desconto por dentro


/*
TODO: Calcular coisas antes da tabela price. Apenas pegar parametros necessarios
  - Se possivel, apenas usar np,pmt,t,pv

  - Tirar calculos de dentro da funcao getTabelaPrice()
*/


function numberToFixed(float $num, int $decimals):float{
    return (float) number_format($num, $decimals, '.', "");
}

function toFixed(float $num,int $decimals):string{
    return number_format($num, $decimals, '.', "");
}


function fe(bool $ehPrimeiraTaxa,float $taxaJuros):float{
    return  ($ehPrimeiraTaxa)?  1 + $taxaJuros : 1;
}


function calcularCoeficienteFinanciamento(float $taxaJuros,int $quantidadeParcelas):float{

    $taxaCorrigida = ($taxaJuros > 1) ? $taxaJuros / 100 : $taxaJuros;
    // taxaCorrigida /= 100;

    return $taxaCorrigida / (1 - pow(1 + $taxaCorrigida, $quantidadeParcelas * -1) );
}


function calcularValorPresente(float $coeficienteFinanciamento,float $taxaJuros,float $precoAPrazo,int $parcelas,bool $ehPrimeiraTaxa):float{
    $f = fe($ehPrimeiraTaxa, $taxaJuros);

    return ($precoAPrazo / $parcelas) * ($f / $coeficienteFinanciamento);
}

function calcularValorFuturo(float $coeficienteFinanciamento,float $taxaJuros,float $precoAVista,int $parcelas,bool $temEntrada):float{

    $resultado = $precoAVista / calcularFatorAplicado($temEntrada,$parcelas,$coeficienteFinanciamento,$taxaJuros);

    return numberToFixed($resultado,2);
}

 function calcularFatorAplicado(bool $temEntrada,int $numParcelas,float $coeficienteFinanciamento,float $taxaJuros):float{
    $f = fe($temEntrada, $taxaJuros);

    return $f/($numParcelas * $coeficienteFinanciamento);
}



function converterJurosMensalParaAnual(float $juros):float{
    $jurosTemp =  $juros /= 100;
    $resultado = (pow(1 + $jurosTemp, 12) - 1) * 100;
    return numberToFixed($resultado, 2);
}


function calcularTaxaDeJuros(float $precoAVista,float  $precoAPrazo,int $numParcelas,bool $temEntrada):float {
    $tolerancia = 0.0001;  
    $taxaDeJuros = 0.1; // Palpite inicial
    $taxaDeJurosAnterior = 0.0;


    $funcao = 0; $derivada = 0;
    $iteracao = 0;

    
    while(abs($taxaDeJurosAnterior - $taxaDeJuros ) >= $tolerancia){
        
        $taxaDeJurosAnterior = $taxaDeJuros;
        $funcao = calcularValorFuncao($precoAPrazo,$taxaDeJuros,$precoAVista,$temEntrada,$numParcelas);

        $derivada = calcularValorDerivadaFuncao($precoAPrazo,$taxaDeJuros,$precoAVista,$temEntrada,$numParcelas);

        $taxaDeJuros = $taxaDeJuros - ($funcao / $derivada);

        $iteracao++;
    }

   
    return $taxaDeJuros;
}

function getValorCorrigido(array $tabelaPrice,int $numeroParcelas,int $mesesAVoltar):float{
    $mesesAVoltar = (int) $mesesAVoltar;
    if($mesesAVoltar == 0 || $mesesAVoltar >= $numeroParcelas){
        return 0;
    }
    else{

        $tamanho = count($tabelaPrice) - 2;

        return $tabelaPrice[$tamanho - $mesesAVoltar ][4];
    }
}

function calcularValorAVoltar(float $pmt,int $numeroParcelas, int$mesesAVoltar):float{
    if( (int) $mesesAVoltar > (int) $numeroParcelas){
        return 0;
    }
    else{
        return $pmt * $mesesAVoltar;
    }
}



function calcularValorFuncao(float $precoAPrazo,float $taxaDeJuros,float $precoAVista,bool $temEntrada,int $numParcelas):float{
    $a = 0; $b = 0; $c = 0;
    if($temEntrada){
        $a = pow(1 + $taxaDeJuros, $numParcelas - 2);
        $b = pow(1 + $taxaDeJuros, $numParcelas - 1);
        $c = pow(1 + $taxaDeJuros, $numParcelas);

        return ($precoAVista * $taxaDeJuros * $b) - ($precoAPrazo/$numParcelas * ($c - 1));
       
    }
    else{
        $a = pow(1 + $taxaDeJuros, -$numParcelas);
        $b = pow(1 + $taxaDeJuros, -$numParcelas - 1 );

        return ($precoAVista * $taxaDeJuros) - ( ($precoAPrazo / $numParcelas) * (1 - $a) ); 
    }
}

function calcularValorDerivadaFuncao(float $precoAPrazo,float $taxaDeJuros,float $precoAVista,bool $temEntrada,int $numParcelas):float{
    $a = 0; $b = 0; $c = 0;
    if($temEntrada){
            $a = pow(1+$taxaDeJuros,$numParcelas-2);
            $b = pow(1 + $taxaDeJuros, $numParcelas - 1);

            return $precoAVista * ($b + ($taxaDeJuros * $a * ($numParcelas - 1) ) ) - ($precoAPrazo * $b);
           
        }
        else{
            $a = pow(1 + $taxaDeJuros, -$numParcelas);
            $b = pow(1 + $taxaDeJuros, -$numParcelas - 1 );
    
            return $precoAVista - ($precoAPrazo * $b); 
        }
}
 
 function calcularPMT(float $precoAVista,float $coeficienteFinanciamento):float{
    return $precoAVista * $coeficienteFinanciamento;
}

 function getTabelaPrice(float $precoAVista,float $pmt,$numParcelas,float $taxaDeJuros,bool $temEntrada):array{

    $jurosTotal = 0;
    $amortizacaoTotal = 0;
    $totalPago = $temEntrada? $pmt : 0;

    $tabelaPrice = array(array("Mês","Prestação", "Juros", "Amortizacao","Saldo Devedor"));


    $juros = $taxaDeJuros; 
    $amortizacao = 0;  
    $saldoDevedor = $precoAVista;


    for($i = 1; $i <= $numParcelas; $i++){


        $juros = ($saldoDevedor * $taxaDeJuros);

        $amortizacao = ($pmt - $juros);

        $saldoDevedor -=  $amortizacao;
        
        $saldoDevedor = $saldoDevedor > 0 ? $saldoDevedor : 0;
     
       
        array_push($tabelaPrice, array($i ,toFixed($pmt,2) , toFixed($juros,3), toFixed($amortizacao,2), toFixed($saldoDevedor,2)));

        $jurosTotal +=  $juros;
        $totalPago += $pmt;
        $amortizacaoTotal += $amortizacao;

    }

    $totalPago = toFixed($totalPago,2);
    $jurosTotal = toFixed($jurosTotal,3);
    $amortizacaoTotal = toFixed($amortizacaoTotal,2);
    $saldoDevedorStr = toFixed($saldoDevedor,2);

    array_push($tabelaPrice, array("Total:", "{$totalPago}","{$jurosTotal}", "{$amortizacaoTotal}","{$saldoDevedorStr}" ) );

    return $tabelaPrice;
}







?>