# Roteiro de testes - Usuários e Repúblicas

Este roteiro cobre a parte do Desenvolvedor 1 antes da integração dos módulos de Despesas e Dashboard/Caixa.

## 1. Página pública e responsividade
- Abrir `/` deslogado.
- Conferir botões de login/cadastro.
- Testar em 375 px, 768 px e desktop.

## 2. Cadastro
- Abrir `/cadastro`.
- Criar usuário com senha de pelo menos 8 caracteres.
- Conferir `users` no banco: a senha deve estar em `password_hash`, nunca em texto puro.
- Tentar cadastrar o mesmo e-mail novamente: deve bloquear.
- Testar e-mail inválido, senha curta e confirmação diferente.

## 3. Login e logout
- Entrar em `/login` com usuário válido.
- Senha errada deve falhar.
- Usuário sem república deve ir para `/republica`.
- Logout deve voltar para `/`.
- Depois do logout, `/dashboard`, `/perfil` e `/republica/moradores` devem redirecionar para `/login`.

## 4. Criar república
- Login com usuário sem república.
- Criar uma república em `/republica`.
- Conferir `republics` e `republic_members` no banco.
- O criador deve estar com `role = admin`.
- Após criar, deve abrir `/dashboard`.

## 5. Entrar por código
- Criar um segundo usuário.
- Fazer login com ele.
- Em `/republica`, usar o `invite_code` da primeira república.
- Conferir no banco: o segundo usuário deve entrar como `member`.

## 6. Tela de moradores
- Com admin, abrir `/republica/moradores`.
- Conferir nome, e-mail, função e código de convite.
- Testar o botão de copiar código.
- Testar em celular e desktop.

## 7. Permissões de moradores
- Como admin, promover um member para admin.
- Rebaixar esse usuário novamente para member.
- Remover o morador.
- Conferir no banco: o registro deve continuar existindo com `active = 0`.
- Reentrar com o mesmo código: o registro deve voltar a `active = 1` e `role = member`.
- Como member, tentar acessar a tela: deve conseguir visualizar, mas não deve ver ações de administrar outros moradores.

## 8. Configurações da república
- Abrir `/republica/configuracoes`.
- Como admin, editar nome e descrição.
- Conferir atualização no banco e no Dashboard/Moradores.
- Como member, a tela deve ser somente leitura.
- Mesmo enviando POST manualmente como member, o backend deve recusar a alteração.

## 9. Perfil
- Abrir `/perfil`.
- Alterar nome, e-mail e telefone.
- Conferir a tabela `users` e o novo nome na interface.
- Tentar trocar para um e-mail que já pertence a outro usuário: deve bloquear.

## 10. Alteração de senha
- Em `/perfil`, informar senha atual e nova senha.
- Senha atual errada deve bloquear.
- Nova senha com menos de 8 caracteres deve bloquear.
- Confirmação diferente deve bloquear.
- Depois da troca, fazer logout e testar: senha antiga não entra; senha nova entra.

## 11. Segurança básica
- Formulários sensíveis possuem token CSRF.
- Login regenera o ID da sessão.
- Senhas usam `password_hash()` / `password_verify()`.
- Queries usam PDO prepared statements.
- Sessão usa cookie HttpOnly e SameSite=Lax.
- Permissão admin é conferida no banco para alterações de moradores e república.
- Dashboard revalida a participação na república no banco.

## 12. Teste de remoção durante sessão
- Entre com um member em um navegador/aba anônima.
- Em outro navegador, admin remove esse member.
- Atualize `/dashboard` do member removido.
- Ele deve ser redirecionado para `/republica` e não continuar usando a república antiga.


## Teste extra — sessão antiga após reset do banco

Se o banco for zerado/recriado enquanto o navegador ainda possuir uma sessão antiga, tente acessar `/dashboard`. O sistema deve invalidar a sessão e redirecionar para `/login?session=expired`, em vez de tratar o usuário antigo como se ainda estivesse autenticado. Depois do novo login, `/republica` deve localizar normalmente a associação ativa em `republic_members`.
