<?php

declare(strict_types=1);

namespace MatthTavares\Pix;

use PHPUnit\Framework\TestCase;

class GeradorTest extends TestCase
{
    private $gerador;

    protected function setUp() : void
    {
        $this->gerador = new Gerador(Gerador::CHAVE_CPF, '01408686406', false, 'Teste PIX com açentós', 0.01, 'Mateus Antônio Tavares', '***', 'João Pessoa');
    }

    public function testGerarCopiaECola()
    {
        $pix = $this->gerador->gerarPix();
        file_put_contents(__DIR__.'/cache/testGerarCopiaECola.json', json_encode($pix));
        $this->assertNotNull($pix);
    }

    public function testGerarQrImagem()
    {
        $pix = $this->gerador->gerarQRCode();
        file_put_contents(__DIR__.'/cache/testGerarQrImagem.png', $pix);
        $this->assertNotNull($pix);
    }

    public function testGerarQrBase()
    {
        $pix = $this->gerador->gerarQRCode(true);
        file_put_contents(__DIR__.'/cache/testGerarQrBase.html', '<img src="' . $pix . '">');
        $this->assertNotNull($pix);
    }
}
