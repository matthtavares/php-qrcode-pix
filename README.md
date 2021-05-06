# php-qrcode-pix
<img src="https://raw.githubusercontent.com/matthtavares/media/main/images/banner_pix.jpg" align="center">

Esse projeto visa facilitar a implementação do QR Code para recebimento de pagamentos do PIX do Banco Central em PHP. Esta versão é adaptada do projeto original desenvolvido por [Renato Batista](http://renato.ovh), disponível [aqui](https://github.com/renatomb/php_qrcode_pix).

O objetivo deste projeto é se tornar o mais minimalista possível e ainda ter todas as funcionalidades necessárias para que seja fácil adicionar código a ele!

Qualquer ⭐️ ou contribuição é apreciada se você gostar do projeto 🤘

## Introdução ao código do PIX

Conforme o [manual de implementação do BR Code](https://www.bcb.gov.br/content/estabilidadefinanceira/SiteAssets/Manual%20do%20BR%20Code.pdf) o Pix adota a representação de dados estruturados de pagamento proposta no padrão EMV®1.

Recomendo a leitura do manual em questão para obter informações iniciais sobre a implementação.

Para se aprofundar nos detalhes técnicos ou se quiser informações sobre os QR Codes dinâmicos também
recomendo a leitura do [Manual de Padrões para Iniciação do Pix](https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/II-ManualdePadroesparaIniciacaodoPix.pdf).

O pagamento através do pix pode ser feito de forma manual com a digitação dos dados do recebedor ou de maneira automatizada onde o recebedor disponibiliza uma requisição de pagamento que será lida pela instituição do pagador. Essa requisição de pagamentyo pode ser em formato texto, que foi denominado Pix Copia e Cola, ou através de um QRCode contendo o mesmo texto do Pix Copia e Cola.

### Formação do código de pagamento

O código de pagamento é um campo de texto alfanumérico (A-Z,0-9) permitindo os caracteres especiais `$ % * + - . / :`.

Na estrutura EMV®1 os dois primeiros dígitos representam o código ID do emv e os dois dígitos seguintes contendo o tamanho do campo. O conteúdo do campo são os caracteres seguintes até a quantidade de caracteres estabelecida.

#### Exemplos de código EMV

No código `000200` temos:

* `00` Código EMV 00 que representa o Payload Format Indicator;
* `02` Indica que o conteúdo deste campo possui dois caracteres;
* `00` O conteúdo deste campo é 00.

No código `5303986` temos:

* `53` Código EMV 53 que indica a Transaction Currency, ou seja: a moeda da transação.
* `03` Indica que o tamanho do campo possui três caracteres;
* `986` Conteúdo do campo é 986, que é o código para  BRL: real brasileiro na ISO4217.

No código `5802BR` temos:

* `58` Código EMV 58 que indica o Country Code.
* `02` Indica que o tamanho do campo possui dois caracteres;
* `BR` Conteúdo do campo é BR, que é o código do país Brasil conforme  ISO3166-1 alpha 2.

Um pix copia e cola contendo os somente os campos acima ficaria `00020053039865802BR`, não há qualquer espaço ou outro caractere separando os campos pois o tamanho de cada campo já está especificado logo após o ID, sendo possível fazer o processamento.

Para facilitar a visualização de um código EMV a partir de qualquer Pix Copia-e-Cola, estou disponibilizando
também o [Decodificador do Pix Copia-e-Cola](http://decoder.qrcodepix.dinheiro.tech/) cujo código fonte está
no repositório [decoder_brcode_pix](https://github.com/renatomb/decoder_brcode_pix).

### Especificades do BR Code

O Pix utiliza o padrão BR Code do banco central, em especial os campos de ID 26 a 51. Esses campos possuem *filhos* que seguem o mesmo padrão do EMV explicado acima.

#### Exemplos BR Code

Observe o código: `26580014br.gov.bcb.pix013642a57095-84f3-4a42-b9fb-d08935c86f47`, nele há:

* `26` Código EMV 26 que representa o Merchant Account Information.
* `58` Indica que o tamanho do campo possui 58 caracteres.
* Demais caracteres representam o conteúdo do campo `0014br.gov.bcb.pix013642a57095-84f3-4a42-b9fb-d08935c86f47`.

Nele, temos dois *filhos*:

O primeiro é `0014br.gov.bcb.pix`:

* `00` ID 00 representa o campo GUI do BRCode (obrigatório).
* `14` Indica que o tamanho do campo possui 14 caracteres.
* `br.gov.bcb.pix` é conteúdo do campo.

O segundo é `013642a57095-84f3-4a42-b9fb-d08935c86f47`:

* `01` O ID 01 representa a chave PIX, que pode ser uma chave aleatória (EVP), e-mail, telefone, CPF ou CNPJ.
* `36` Indica que o tamanho do campo possui 36 caracteres.
* `42a57095-84f3-4a42-b9fb-d08935c86f47` indica a chave pix do destinatário, no caso a chave em questão está no formato UUID que é uma chave aleatória (EVP).

Se você está apreciando o conteúdo deste trabalho, sinta-se livre para fazer qualquer doação para a chave `42a57095-84f3-4a42-b9fb-d08935c86f47` :)

## Como instalar e utilizar
```
composer install matthtavares/php-qrcode-pix
```

### Dependências

Todas as dependências deste projeto serão gerenciadas pelo Composer. Você só precisar instalar o projeto e usar.

## Observações

## Nota sobre o uso de chaves EVP

As chaves aleatórias (Endereço Virtual de Pagamento - EVP) devem ser informadas em letras minúsculas.

## Nota sobre o uso da descrição do pagamento (campo 26 02)

A descrição do pagamento é exibida para o pagador no ato da confirmação do pix no aplicativo do cliente, nos bancos abaixo-relacionados essa informação consta no extrato da conta de quem recebeu o pix:

* Nubank;

## Nota sobre o uso do identificador de transação

Conforme o manual [manual de implementação do BR Code](https://www.bcb.gov.br/content/estabilidadefinanceira/SiteAssets/Manual%20do%20BR%20Code.pdf), pg 5, nota de rodapé, temos: "Conclui-se que, se o gerador do QR optar por não utilizar um  transactionID, o valor `***` deverá ser usado para indicar essa escolha.

### Nubank

O identificador usado não é exibido no extrato da NuConta. A descrição da transação (campo 26 02) é facilmente
identificável no aplicativo.

### Itaú

Itaú recusa o pix de qualquer identificador de transação que não tenha sido gerado previamente no aplicativo deles. Conforme [informações que obtive](https://github.com/bacen/pix-api/issues/214) para utilizar qr code gerado fora do aplicativo do itaú, é necessário entrar em contato com o gerente para que o mesmo realize a liberação da conta para uso de qrcoe de terceiros. Se não houver essa liberação o Itaú está recusando o recebimento do pix com base no identificador utilizado.

## Testes realizados

Esta implementação foi testada, realizando a leitura do QRCode, Pix Copia-e-Cola, envio de Pix para outra instituição e Recebimento de pix de outra instituição, nos aplicativos dos seguintes bancos:

* Banco Inter;
* Sofisa direto;
* NuBank;
* C6 Bank;
* AgZero / Safra Wallet;
* BMG;
* PagBank;
* Digio;
* MercadoPago;
* Itau;
* Bradesco;
* BS2;
* Banco do Brasil;
* Santander;
* Sicredi;
* AgiBank;
* GerenciaNet;