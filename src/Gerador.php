<?php

namespace MatthTavares\Pix;

use LengthException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * This class provides an access api to public Instagram data.
 *
 * @author Mateus Tavares <contato@mateustavares.com.br>
 * @author Renato Monteiro Batista (http://renato.ovh)
 * @copyright 2021 Mateus Tavares
 */
class Gerador
{
    const CHAVE_CPF = 'CPF';
    const CHAVE_CNPJ = 'CNPJ';
    const CHAVE_TELEFONE = 'TELEFONE';
    const CHAVE_EMAIL = 'EMAIL';
    const CHAVE_ALEATORIA = 'ALEATORIA';

    /**
     * Matriz com os valores para gerar PIX.
     */
    protected $px = [];

    /**
     * Novo Gerador
     *
     * @author Mateus Tavares
     * @param string $tipoChave Tipo da chave a ser utilizada.
     * @param string $chave Chave da conta PIX.
     * @param boolean $pagamentoUnico Informa se este PIX vai ser usado somente uma vez.
     * @param string $descricao Descrição do pagamento.
     * @param float $valor Valor do pagamento.
     * @param string $beneficiario Nome do recebedor do pagamento.
     * @param string $identificador Identificação do pagamento.
     * @param string $cidade Cidade onde vai ser realizado o pagamento.
     */
    public function __construct( 
        string $tipoChave,
        string $chave, 
        bool $pagamentoUnico,
        string $descricao,
        float $valor = 0.01,
        string $beneficiario,
        string $identificador = '***',
        string $cidade
    ) {
        // Payload Format Indicator, Obrigatório, valor fixo: 01
        $this->px[00] = "01";
        // Indica arranjo específico; “00” (GUI) obrigatório e valor fixo: br.gov.bcb.pix 
        $this->px[26][00] = "BR.GOV.BCB.PIX";
        // Merchant Category Code “0000” ou MCC ISO18245
        $this->px[52] = "0000";
        // Moeda, “986” = BRL: real brasileiro - ISO4217
        $this->px[53] = "986";
        // “BR” – Código de país ISO3166-1 alpha 2
        $this->px[58] = "BR";

        /**
         * O campo 62/50 é um campo facultativo, que indica a versão do arranjo de pagamentos que está sendo usada.
         * 00: Payment system specific template - GUI
         * 01: Payment system specific template - versão
         */
        $this->px[62][50][00] = "BR.GOV.BCB.BRCODE";
        $this->px[62][50][01] = "1.0.0";

        $this->setChave($tipoChave, $chave);
        $this->setPagamentoUnico($pagamentoUnico);
        $this->setDescricao($descricao);
        $this->setValor($valor);
        $this->setBeneficiario($beneficiario);
        $this->setIdentificador($identificador);
        $this->setCidade($cidade);
    }

    /**
     * Define a chave do destinatário do PIX, pode ser EVP, e-mail, CPF ou CNPJ.
     *
     * @author Mateus Tavares
     * @param string $tipo
     * @param string $chave
     * @throws LengthException
     */
    public function setChave( string $tipo, string $chave )
    {
        // Precisa informar um tipo de chave e seguindo as constantes da classe
        if( empty($tipo) && in_array($tipo, [self::CHAVE_CPF, self::CHAVE_CNPJ, self::CHAVE_TELEFONE, self::CHAVE_EMAIL, self::CHAVE_ALEATORIA]) )
            throw new LengthException("Defina um tipo de chave para ser utilizada no PIX.");

        // Precisa informar uma chave não vazia
        if( empty($chave) )
            throw new LengthException("Defina uma chave para ser utilizada no PIX.");

        // Converte a chave para minúsculo
        $chave = strtolower($chave);

        // Se a chave for CPF, CNPJ ou TELEFONE
        if( in_array($tipo, [self::CHAVE_CPF, self::CHAVE_CNPJ, self::CHAVE_TELEFONE]) ) {
            // Deixa apenas os números
            $chave = preg_replace('/[^\d]/i', '', $chave);

            // Valida tamanho de CPF
            if( $tipo == self::CHAVE_CPF && strlen($chave) != 11 )
                throw new LengthException("A chave CPF tem tamanho inválido.");

            // Valida tamanho de CNPJ
            if( $tipo == self::CHAVE_CNPJ && strlen($chave) != 14 )
                throw new LengthException("A chave CNPJ tem tamanho inválido.");

            // Formata a chave para telefone
            if( $tipo == self::CHAVE_TELEFONE ) {
                if( strlen($chave) < 10 && strlen($chave) > 11 )
                    throw new LengthException("A chave TELEFONE tem tamanho inválido.");
                $chave = sprintf("%s%d%d", '+55', substr($chave, 0, 2), substr($chave, 2, 9));
            }
        } else if( $tipo == self::CHAVE_EMAIL && strlen($chave) > 77 ) {
            throw new LengthException("A chave EMAIL tem tamanho superior a 77 caracteres.");
        } else if( $tipo == self::CHAVE_ALEATORIA && strlen($chave) != 36 ) {
            throw new LengthException("A chave ALEATORIA tem tamanho inválido.");
        }

        $this->px[26][01] = $chave;
    }

    /**
     * Define se o QR Code vai ser de pagamento único (só puder ser utilizado uma vez).
     * Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez.
     *
     * @author Mateus Tavares
     * @param boolean $bit
     */
    public function setPagamentoUnico( bool $bit )
    {
        if( $bit )
            $this->px[01] = "12";
        else
            unset($this->px[01]);
    }

    /**
     * Informa a descrição do pagamento (Opcional).
     *
     * @author Mateus Tavares
     * @param string $descricao
     * @throws LengthException
     */
    public function setDescricao( string $descricao )
    {
        if (strlen($descricao) > 99)
            throw new LengthException("Descrição excede o tamanho máximo de 99 caracteres.");

        $this->px[26][02] = $this->removeCaracteresEspeciais($descricao);
    }

    /**
     * Valor da transação, se não informado o cliente especifica no próprio app. 
     * O tamanho máximo do valor formatado é de 13 caracteres.
     *
     * @author Mateus Tavares
     * @param float $valor
     * @throws LengthException
     */
    public function setValor( float $valor = 0.01 )
    {
        $valor = number_format($valor, 2, '.', '');

        if (strlen($valor) > 13)
            throw new LengthException("Valor formatado excede o tamanho máximo de 13 caracteres.");

        $this->px[54] = $valor;
    }

    /**
     * Nome do beneficiário/recebedor do pagamento. 
     * Máximo: 25 caracteres.
     *
     * @author Mateus Tavares
     * @param string $nome
     * @throws LengthException
     */
    public function setBeneficiario( string $nome )
    {
        if (strlen($nome) > 25)
            throw new LengthException("Nome do beneficiário excede o tamanho máximo de 25 caracteres.");

        $this->px[59] = strtoupper($this->removeCaracteresEspeciais($nome));
    }

    /**
     * Código identificador do pagamento. 
     * Máximo: 36 caracteres.
     *
     * @author Mateus Tavares
     * @param string $id
     * @throws LengthException
     */
    public function setIdentificador( string $id = '***' )
    {
        if (strlen($id) > 36)
            throw new LengthException("Código identificador excede o tamanho máximo de 36 caracteres.");

        $this->px[62][05] = $id;
    }

    /**
     * Nome da cidade onde é efetuada a transação.
     * Máximo: 15 caracteres.
     *
     * @author Mateus Tavares
     * @param string $cidade
     * @throws LengthException
     */
    public function setCidade( string $cidade )
    {
        if (strlen($cidade) > 15)
            throw new LengthException("Nome da cidade excede o tamanho máximo de 15 caracteres.");

        $this->px[60] = strtoupper($this->removeCaracteresEspeciais($cidade));
    }

    /**
     * Este método ajusta os textos removendo caracteres especiais.
     *
     * @author Mateus Tavares
     * @param string $texto
     * @return string
     */
    protected function removeCaracteresEspeciais( string $texto ): string
    {
        return preg_replace(
            ["/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/",
             "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/", "/(ç)/", "/(Ç)/"], 
            explode(" ", "a A e E i I o O u U n N c C"),
            $texto);
    }


    /**
     * Esta rotina monta o código do pix conforme o padrão EMV
     * Todas as linhas são compostas por [ID do campo][Tamanho do campo com dois dígitos][Conteúdo do campo]
     * Caso o campo possua filhos esta função age de maneira recursiva.
     *
     * @author Renato Monteiro Batista
     * @param array $px
     * @return string
     */
    protected function montaPix( array $px ): string
    {
        $ret = '';
        foreach ($px as $k => $v) {
            if (!is_array($v)) {
                // Formata o campo valor com 2 digitos
                if ( $k == 54 )
                    $v = number_format($v, 2, '.', '');
                $ret .= $this->c2($k).$this->cpm($v).$v;
            } else {
                $conteudo = $this->montaPix($v);
                $ret .= $this->c2($k).$this->cpm($conteudo).$conteudo;
            }
        }
        return $ret;
    }

    /**
     * Esta função auxiliar retorna a quantidade de caracteres do texto $tx com dois dígitos.
     *
     * @author Renato Monteiro Batista
     * @param string $tx
     * @return string
     * @throws LengthException
     */
    protected function cpm( string $tx ): string
    {
        if (strlen($tx) > 99)
            throw new LengthException("'{$tx}' excede o tamanho máximo de 99 caracteres.");
        return $this->c2(strlen($tx));
    }

    /**
     * Esta função auxiliar trata os casos onde o tamanho do campo for menor que 10 (numérico) 
     * acrescentando o dígito 0 a esquerda.
     *
     * @author Renato Monteiro Batista
     * @param string $input
     * @return string
     */
    protected function c2( string $input ): string
    {
        return str_pad($input, 2, "0", STR_PAD_LEFT);
    }

    /**
     * The PHP version of the JS str.charCodeAt(i).
     *
     * @param string $str
     * @param int    $i
     * @return int
     */
    protected function charCodeAt( string $str, int $i) 
    {
        return ord(substr($str, $i, 1));
    }

    /**
     * Esta função auxiliar calcula o CRC-16/CCITT-FALSE.
     *
     * @author evilReiko (https://stackoverflow.com/users/134824/evilreiko)
     * @param string $str
     */
    protected function crcChecksum( string $str ): string
    {
        $crc = 0xFFFF;
        $strlen = strlen($str);
        for($c = 0; $c < $strlen; $c++) {
            $crc ^= $this->charCodeAt($str, $c) << 8;
            for($i = 0; $i < 8; $i++) {
                if($crc & 0x8000) {
                   $crc = ($crc << 1) ^ 0x1021;
                } else {
                   $crc = $crc << 1;
                }
            }
        }
        $hex = $crc & 0xFFFF;
        $hex = dechex($hex);
        $hex = strtoupper($hex);
        return $hex;
    }


    /**
     * Gera a string do copia e cola.
     *
     * @author Mateus Tavares
     * @return string
     */
    public function gerarPix(): string
    {
        // A função montaPix prepara todos os campos existentes antes do CRC (campo 63).
        $pix = $this->montaPix($this->px);

        // O CRC deve ser calculado em cima de todo o conteúdo, inclusive do próprio 63.
        // O CRC tem 4 dígitos, então o campo será um 6304.

        $pix .= "6304"; // Adiciona o campo do CRC no fim da linha do pix.
        $pix .= $this->crcChecksum($pix); // Calcula o checksum CRC16 e acrescenta ao final.

        return $pix;
    }

    /**
     * Gera a imagem do QR Code em JPG.
     *
     * @author Mateus Tavares
     * @param boolean $base64 
     * @return mixed
     */
    public function gerarQRCode( bool $base64 = false )
    {
        $options = new QROptions([
            'outputType'  => QRCode::OUTPUT_IMAGE_JPG, // setando o output como JPG
            'imageBase64' => $base64, // Se o retorno é como Base64 ou binário.
        ]);
        return (new QRCode($options))->render($this->gerarPix());
    }
}
