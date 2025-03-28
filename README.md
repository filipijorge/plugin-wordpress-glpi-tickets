# Plugin de Wordpress para criar chamados no GLPI

Este plugin cria um shortcode para ser adicionado à uma página do Wordpress.

A página criada fica com um formulário que só é visível para usuário logados. Ao preencher o formulário e clicar no botão abrir chamado, um chamado é criado no GLPI com o usuário logado no Wordpress como requerente.

GLPI e Wordpress precisam ter usuários iguais. No ambiente em que foi feito, ambos possuem integração dom LDAP.

O plugin deve ficar dentro do diretório  /wp-content/plugins/nome-do-seu-plugin.

No GLPI precisa ser gerado um token de aplicação e um token de usuário que tenha permissão de criar chamados. O autor dos chamados vai ser o usuário que forneceu o token.

Editar as seguintes linhas com os tokens gerados e o ID do origem da requisição:
```
$app_token = "APP TOKEN";
$user_token = "USER TOKEN";
"requesttypes_id" => 8,
```
Origens de requisição podem ser criadas em: `https://url-do-glpi/front/requesttype.php`
