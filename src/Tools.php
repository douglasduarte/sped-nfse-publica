<?php

namespace NFePHP\NFSePublica;

/**
 * Class for comunications with NFSe webserver in Nacional Standard
 *
 * @category  NFePHP
 * @package   NFePHP\NFSePublica
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-publica for the canonical source repository
 */

use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;
use NFePHP\NFSePublica\Common\Signer;
use NFePHP\NFSePublica\Common\Tools as BaseTools;

class Tools extends BaseTools
{
    const CANCEL_ERRO_EMISSAO = 1;
    const CANCEL_SERVICO_NAO_CONCLUIDO = 2;
    const CANCEL_DUPLICIDADE = 4;

    protected $xsdpath;

    /**
     * Constructor
     * @param string $config
     * @param Certificate $cert
     */
    public function __construct($config, Certificate $cert)
    {
        parent::__construct($config, $cert);
        $path = realpath(
            __DIR__ . '/../storage/schemes'
        );
        $this->xsdpath = $path . '/schema_nfse_v03.xsd';
    }

    /**
     * Solicita o cancelamento de NFSe (SINCRONO)
     * @param integer $numero
     * @param integer $codigo
     * @param string $motivo
     * @return string
     */
    public function cancelarNfse($numero, $codigo = self::CANCEL_ERRO_EMISSAO, $motivo = null)
    {
        $operation = 'CancelarNfse';
        $pedido = "<Pedido>"
            . "<InfPedidoCancelamento id=\"$numero\">"
            . "<IdentificacaoNfse>"
            . "<Numero>{$numero}</Numero>";
        if (!empty($this->config->cnpj)) {
            $pedido .= "<Cnpj>{$this->config->cnpj}</Cnpj>";
        } else {
            $pedido .= "<Cpf>{$this->config->cpf}</Cpf>";
        }
        $pedido .= "<InscricaoMunicipal>{$this->config->im}</InscricaoMunicipal>"
            . "<CodigoMunicipio>{$this->config->cmun}</CodigoMunicipio>"
            . "</IdentificacaoNfse>"
            . "<CodigoCancelamento>$codigo</CodigoCancelamento>";
        if ($codigo == self::CANCEL_ERRO_EMISSAO) {
            $pedido .= "<MotivoCancelamento>{$motivo}</MotivoCancelamento>";
        }
        $pedido .= "</InfPedidoCancelamento>"
            . "</Pedido>";
        $content = "<CancelarNfseEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . $pedido
            . "</CancelarNfseEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'InfPedidoCancelamento',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'Pedido'
        );
        $content = str_replace(
            ['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'],
            '',
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Cancelamento com substituição por novo RPS
     * @param integer $numero_nfse_a_cancelar
     * @param integer $codigo 1-erro emissão 2-serviço não prestado 4-emissão em duplicidade
     * @param RpsInterface $novorps
     * @return string
     */
    public function substituirNfse(
        $numero_nfse_a_cancelar,
        RpsInterface $novorps,
        $codigo = self::CANCEL_ERRO_EMISSAO
    )
    {
        $operation = "SubstituirNfse";
        $novorps->config($this->config);
        $pedido = "<Pedido>"
            . "<InfPedidoCancelamento id=\"cancel\">"
            . "<IdentificacaoNfse>"
            . "<Numero>" . sprintf("%015d", $numero_nfse_a_cancelar) . "</Numero>"
            . "<Cnpj>{$this->config->cnpj}</Cnpj>"
            . "<InscricaoMunicipal>{$this->config->im}</InscricaoMunicipal>"
            . "<CodigoMunicipio>{$this->config->cmun}</CodigoMunicipio>"
            . "</IdentificacaoNfse>"
            . "<CodigoCancelamento>{$codigo}</CodigoCancelamento>"
            . "</InfPedidoCancelamento>"
            . "</Pedido>";
        $content = "<SubstituirNfseEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . "<SubstituicaoNfse id=\"subst\">"
            . $pedido
            . $novorps->render()
            . "</SubstituicaoNfse>"
            . "</SubstituirNfseEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'InfRps',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'Rps'
        );
        $content = Signer::sign(
            $this->certificate,
            $content,
            'InfPedidoCancelamento',
            'Id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'Pedido'
        );
        $content = Signer::sign(
            $this->certificate,
            $content,
            'SubstituicaoNfse',
            'Id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'SubstituirNfseEnvio'
        );
        $content = str_replace(
            ['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'],
            '',
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta Lote RPS (SINCRONO) após envio com recepcionarLoteRps() (ASSINCRONO)
     * complemento do processo de envio assincono.
     * Que deve ser usado quando temos mais de um RPS sendo enviado
     * por vez.
     * @param string $protocolo
     * @return string
     */
    public function consultarLoteRps($protocolo)
    {
        $operation = 'ConsultarLoteRps';
        $content = "<ConsultarLoteRpsEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . $this->prestador
            . "</ConsultarLoteRpsEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'Prestador',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            ''
        );
        $content = str_replace(
            ['</ConsultarLoteRpsEnvio>'],
            [
                "<Protocolo>{$protocolo}</Protocolo>"
                . "</ConsultarLoteRpsEnvio>"
            ],
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe emitidas por faixa de numeros (SINCRONO)
     * @param integer $numero_ini
     * @param integer $numero_fim
     * @return string
     */
    public function consultarNfseFaixa($numero_ini, $numero_fim)
    {
        $operation = 'ConsultarNfseFaixa';
        $content = "<ConsultarNfseFaixaEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . $this->prestador
            . "</ConsultarNfseFaixaEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'Prestador',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            ''
        );
        $content = str_replace(
            ['</ConsultarNfseFaixaEnvio>'],
            [
                "<Faixa>"
                . "<NumeroNfseInicial>{$numero_ini}</NumeroNfseInicial>"
                . "<NumeroNfseFinal>{$numero_fim}</NumeroNfseFinal>"
                . "</Faixa></ConsultarNfseFaixaEnvio>"
            ],
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe por RPS (SINCRONO)
     * @param integer $numero
     * @param string $serie
     * @param integer $tipo
     * @return string
     */
    public function consultarNfseRps($numero, $serie, $tipo)
    {
        $operation = "ConsultarNfsePorRps";
        $content = "<ConsultarNfseRpsEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . "<IdentificacaoRps>"
            . "<Numero>{$numero}</Numero>"
            . "<Serie>{$serie}</Serie>"
            . "<Tipo>{$tipo}</Tipo>"
            . "</IdentificacaoRps>"
            . $this->prestador
            . "</ConsultarNfseRpsEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'Prestador',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            ''
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Envia LOTE de RPS para emissão de NFSe (ASSINCRONO)
     * @param array $arps Array contendo de 1 a 2 RPS::class
     * @param string $lote Número do lote de envio
     * @return string
     * @throws \Exception
     */
    public function recepcionarLoteRps($arps, $lote)
    {
        $operation = 'RecepcionarLoteRps';
        $no_of_rps_in_lot = count($arps);
        if ($no_of_rps_in_lot > 50) {
            throw new \Exception('O limite é de 50 RPS por lote enviado em modo sincrono.');
        }
        $content = '';
        foreach ($arps as $rps) {
            $rps->config($this->config);
            $content .= $rps->render();
        }
        $contentmsg = "<EnviarLoteRpsEnvio xmlns=\"{$this->wsobj->msgns}\" "
            . "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" "
            . "xsi:schemaLocation=\"http://www.publica.inf.br schema_nfse_v03.xsd\">"
            . "<LoteRps versao=\"{$this->wsobj->version}\">"
            . "<NumeroLote>$lote</NumeroLote>"
            . "<Cnpj>{$this->config->cnpj}</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "<QuantidadeRps>$no_of_rps_in_lot</QuantidadeRps>"
            . "<ListaRps>"
            . $content
            . "</ListaRps>"
            . "</LoteRps>"
            . "</EnviarLoteRpsEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $contentmsg,
            'InfRps',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'Rps'
        );
        $content = Signer::sign(
            $this->certificate,
            $content,
            'LoteRps',
            'Id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'EnviarLoteRpsEnvio'
        );
        $content = str_replace(
            ['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'],
            '',
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Solicita a emissão de uma NFSe de forma SINCRONA
     * @param RpsInterface $rps
     * @return string
     */
    public function gerarNfse(RpsInterface $rps)
    {
        $operation = "GerarNfse";
        $rps->config($this->config);
        $content = "<GerarNfseEnvio xmlns=\"{$this->wsobj->msgns}\">"
            . $rps->render()
            . "</GerarNfseEnvio>";
        $content = Signer::sign(
            $this->certificate,
            $content,
            'InfRps',
            'id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null],
            'Rps'
        );
        $content = str_replace(
            ['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'],
            '',
            $content
        );
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }
}
