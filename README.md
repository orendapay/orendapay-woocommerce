# OrendaPay WooCommerce #
[![Licença do Software][badge-license]](LICENSE)

English: Generate bank billet and credit card transactions at transparency Checkout of your Woocommerce from WordPress using Orenda Pay.  **[OrendaPay](https://www.orendapay.com.br/)**. 

## Descrição ##
Implemente transações por cartão de crédito transparente e boletos bancários no Checkout do seu Woocommerce usando a solução de pagamento **[OrendaPay](https://www.orendapay.com.br/)**

## Sobre a Integração ##

A integração OrendaPay Woocommerce é uma solução completa para cobrar através boletos bancários ou cartão de crédito no WooCommerce. E ainda conta com os retornos automáticos (callbacks), que serão responsáveis por alterar os status dos seus pedidos no Woocommerce de forma automática, atualizando sempre o status do seu pedido para *processando*.

- Geração de boletos bancários
- Checkout transparente por Cartão de Crédito
- Boletos registrados
- Boletos no formato PDF
- Retorno automático da situação do Pedido
- Melhor taxa de boletos bancários do Brasil

## Pré Requisitos ##

- Ter uma conta ativa no **[OrendaPay](https://www.orendapay.com.br/)**
- Habilitar e obter no Painel **[OrendaPay](https://www.orendapay.com.br/)** o ID e TOKEN de integração da sua conta.
- Ter o WooCommerce versão 2.2 ou superior já instalado em seu Wordpress, 
- Ter instalado o Plugin https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/, este plugin é responsável por habilitar no Checkout Woocommerce campos extras de uso exclusivo do Brasil, como, CPF, CNPJ, CEP, etc...

## Instalação do Plugin OrendaPay Woocommerce ##

A instalação do OrendaPay Woocommerce segue o padrão de qualquer instalação de Plugin no Wordpress, e é bem simples, veja o passo a passo da instalação e configuração:

Antes de iniciar, baixe o Plugin de integração OrendaPay Woocommerce neste [repositório GitHub](https://github.com/orendapay/orendapay-woocommerce/), clique no botão verde "Clone or Download" e escolha "Download ZIP"

Após o download, basta seguir as etapas abaixo:

1) Acesse seu Painel de Controle do WordPress (wp-admin)
2) Acesse o menu Plugins > Adicionar Novo
3) Clique no botão "Enviar Plugin" e importe o arquivo zip baixado.
4) Depois no menu Plugins, encontre o Plugin instalado (OrendaPay) e clique em "Ativar"
5) Após a ativação, navegue pelo menu Woocommerce > Configurações
6) Na página que abrir, clique na aba "Pagamentos" e Habilite o OrendaPay
7) Depois clique no botão Gerenciar que aparecerá na frente do OrendaPay e informe os dados de integração.

## Dúvidas? ##

Temos uma página exclusiva para dúvidas da integração OrendaPay Woocommerce, **[Dúvidas OrendaPay Woocommerce](https://www.orendapay.com.br/ecommerce)**

## Colaborar ##

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/orendapay/orendapay-woocommerce/).

## Licença

GNU GPLv3. Por favor, veja o [Arquivo de Licença](LICENSE) para mais informações.


[badge-license]: https://img.shields.io/badge/license-GPLv3-blue.svg
