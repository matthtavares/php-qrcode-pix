<?php
/**
 *
 * @filesource   imagem.php
 * @created      06/05/2021
 * @author       Mateus Tavares <contato@mateustavares.com.br>
 * @copyright    2021 Mateus Tavares
 * @license      MIT
 */
require_once __DIR__.'/../vendor/autoload.php';

use MatthTavares\Pix\Gerador;

header('Content-type: image/png');

$gerador = new Gerador(Gerador::CHAVE_ALEATORIA, '4b5e9b53-bded-4f60-8ba3-e1b2cc3088c5', false, 'Teste PIX.', 0.01, 'Mateus Antônio Tavares', '***', 'João Pessoa');

// Obtém os dados do QR Code gerado
echo $gerador->gerarQRCode(Gerador::OUTPUT_PNG);