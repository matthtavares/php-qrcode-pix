<?php
/**
 *
 * @filesource   html.php
 * @created      06/05/2021
 * @author       Mateus Tavares <contato@mateustavares.com.br>
 * @copyright    2021 Mateus Tavares
 * @license      MIT
 */
require_once __DIR__.'/../vendor/autoload.php';

use MatthTavares\Pix\Gerador;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>QRCode test</title>
    <style>
        body{
            margin: 5em;
            padding: 0;
        }

        div.qrcode{
            margin: 0;
            padding: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="qrcode">
<?php
    $gerador = new Gerador(Gerador::CHAVE_ALEATORIA, '4b5e9b53-bded-4f60-8ba3-e1b2cc3088c5', false, 'Teste PIX.', 0.01, 'Mateus Antônio Tavares', '***', 'João Pessoa');

    // Obtém o QR Code como PNG em Base64
    $pix = $gerador->gerarQRCode(Gerador::OUTPUT_PNG, true);
    echo '<img src="' . $pix . '">';
?>
    </div>
</body>
</html>