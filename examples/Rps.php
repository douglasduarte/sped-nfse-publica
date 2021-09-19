<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\NFSePublica\Rps;

$std = new \stdClass();
$std->version = '2.02'; //false
$std->dataemissao = '2018-10-31'; //false
$std->status = 1;  // true
$std->competencia = '2018-10-01'; //true
$std->regimeespecialtributacao = 1; 
$std->optantesimplesnacional = 1; // true
$std->incentivofiscal = 2; // true

$std->identificacaorps = new \stdClass(); //false
$std->identificacaorps->numero = 11; 
$std->identificacaorps->serie = '1'; 
$std->identificacaorps->tipo = 1;

$std->servico = new \stdClass(); //true
$std->servico->issretido = 2; //true
$std->servico->responsavelretencao = null; //false
$std->servico->itemlistaservico = '11.01'; //true
$std->servico->codigocnae = '8599603'; //false
$std->servico->codigoTributacaomunicipio = null; //false
$std->servico->discriminacao = 'Teste de RPS'; //true
$std->servico->codigomunicipio = '3106200'; // true
$std->servico->codigopais = null; //false
$std->servico->exigibilidadeiss  = 1; //true
$std->servico->municipioincidencia  = '3106200'; // false
$std->servico->numeroprocesso = null; //false

$std->servico->valores = new \stdClass(); //true
$std->servico->valores->valorservicos = 100.00; //true
$std->servico->valores->valordeducoes = 10.00; //false
$std->servico->valores->valorpis = 10.00; //false
$std->servico->valores->valorcofins = 10.00; //false
$std->servico->valores->valorinss = 10.00; //false
$std->servico->valores->valorir = 10.00; //false
$std->servico->valores->valorcsll = 10.00; //false
$std->servico->valores->outrasretencoes = 10.00; //false
$std->servico->valores->valoriss = 10.00; //false
$std->servico->valores->aliquota = 5; //false
$std->servico->valores->descontoincondicionado = 10.00; //false
$std->servico->valores->descontocondicionado = 10.00; //false

$std->tomador = new \stdClass(); //false
$std->tomador->cnpj = "99999999000191"; //false
$std->tomador->cpf = "12345678901"; //false
$std->tomador->razaosocial = "Fulano de Tal"; //false 
$std->tomador->telefone = '123456789'; //false
$std->tomador->email = 'fulano@mail.com'; //false

$std->tomador->endereco = new \stdClass(); //false
$std->tomador->endereco->endereco = 'Rua das Rosas'; //false
$std->tomador->endereco->numero = '111'; //false
$std->tomador->endereco->complemento = 'Sobre Loja'; //false
$std->tomador->endereco->bairro = 'Centro'; //false
$std->tomador->endereco->codigomunicipio = '3106200'; //false
$std->tomador->endereco->uf = 'MG'; //false
$std->tomador->endereco->codigopais = null; //false
$std->tomador->endereco->cep = '30160010'; //false

$std->intermediarioservico = new \stdClass(); //false
$std->intermediarioservico->cnpj = '99999999000191'; //false 
$std->intermediarioservico->cpf = null; //false
$std->intermediarioservico->inscricaomunicipal = '8041700010';
$std->intermediarioservico->razaosocial = "Beltrano da Silva";

$std->construcaocivil = new \stdClass(); //false
$std->construcaocivil->codigoobra = '1234'; //false
$std->construcaocivil->art = '1234'; //true


$configArr = [
    'cnpj'  => '99999999000191',
    'im'    => '1733160024',
    'cmun'  => '4204608', //ira determinar as urls e outros dados
    'razao' => 'Empresa Test Ltda',
    'tpamb' => 2 //1-producao, 2-homologacao
];

$config = (object) $configArr;

$rps = new Rps($std);
$rps->config($config);

header("Content-type: text/xml");
echo $rps->render();



