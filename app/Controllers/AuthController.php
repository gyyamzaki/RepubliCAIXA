<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use InvalidArgumentException;
use RuntimeException;

final class AuthController
{
    // CONFIGURAÇÕES DE SEGURANÇA
    private const MAX_TENTATIVAS = 5;
    private const JANELA_SEGUNDOS = 900;

    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // TELA DE CADASTRO
    public function showRegister(): void
    {
        $pageTitle = 'Criar conta - RepubliCAIXA';

        $errors = [];
        $name = '';
        $email = '';

        require dirname(__DIR__)
            . '/Views/auth/register.php';
    }

    // CADASTRAR USUÁRIO
    public function register(): void
    {
        $name = is_string($_POST['name'] ?? null)
            ? trim($_POST['name'])
            : '';

        $email = is_string($_POST['email'] ?? null)
            ? trim($_POST['email'])
            : '';

        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

        $passwordConfirmation = is_string(
            $_POST['password_confirmation'] ?? null
        )
            ? $_POST['password_confirmation']
            : '';

        $errors = [];

        if (strlen($name) < 2) {
            $errors[] = 'Informe um nome válido.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        }

        if (strlen($password) < 8) {
            $errors[] =
                'A senha deve possuir pelo menos 8 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            $errors[] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            $pageTitle = 'Criar conta - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/register.php';

            return;
        }

        try {
            $this->authService->register(
                $name,
                $email,
                $password
            );

        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();

            $pageTitle = 'Criar conta - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/register.php';

            return;
        }

        header('Location: /login?registered=1');
        exit;
    }

    // TELA DE LOGIN
    public function showLogin(): void
    {
        $pageTitle = 'Login - RepubliCAIXA';

        $errors = [];
        $email = '';

        require dirname(__DIR__)
            . '/Views/auth/login.php';
    }

    // PROCESSAR LOGIN
    public function login(): void
    {
        $email = is_string($_POST['email'] ?? null)
            ? trim($_POST['email'])
            : '';

        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

        $errors = [];

        // Mensagem genérica para não revelar contas existentes
        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $password === ''
        ) {
            $errors[] = 'E-mail ou senha incorretos.';

        } else {
            try {
                // CONSULTAR LIMITADOR DE TENTATIVAS
                $user = $this->autenticarComLimite(
                    $email,
                    $password,
                    $errors
                );

                if ($user !== null) {

                    // RENOVAR IDENTIFICADOR DA SESSÃO
                    Session::regenerate();

                    // SALVAR USUÁRIO AUTENTICADO
                    Session::set('user', $user);

                    header('Location: /');
                    exit;
                }

            } catch (RuntimeException $exception) {
                error_log(
                    'Falha no limitador de login: '
                    . $exception->getMessage()
                );

                http_response_code(503);

                $errors[] =
                    'Login temporariamente indisponível. '
                    . 'Tente novamente mais tarde.';
            }
        }

        $pageTitle = 'Login - RepubliCAIXA';

        require dirname(__DIR__)
            . '/Views/auth/login.php';
    }

    // AUTENTICAR COM LIMITE DE TENTATIVAS
    private function autenticarComLimite(
        string $email,
        string $password,
        array &$errors
    ): ?array {

        // ARMAZENAMENTO FORA DA PASTA PUBLIC
        $pasta = dirname(__DIR__, 2)
            . '/storage/login-rate-limit';

        if (
            !is_dir($pasta)
            && !@mkdir($pasta, 0700, true)
            && !is_dir($pasta)
        ) {
            throw new RuntimeException(
                'Não foi possível criar o diretório de controle.'
            );
        }

        // LIMITAÇÃO POR ENDEREÇO IP
        $ip = (string) (
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );

        $caminho = $pasta . '/'
            . hash('sha256', $ip)
            . '.json';

        $arquivo = @fopen($caminho, 'c+');

        if ($arquivo === false) {
            throw new RuntimeException(
                'Não foi possível abrir o controle de tentativas.'
            );
        }

        try {

            // EVITAR MODIFICAÇÕES SIMULTÂNEAS
            if (!flock($arquivo, LOCK_EX)) {
                throw new RuntimeException(
                    'Não foi possível bloquear o controle de tentativas.'
                );
            }

            rewind($arquivo);

            $conteudo = stream_get_contents($arquivo);

            $dados = json_decode(
                is_string($conteudo) ? $conteudo : '',
                true
            );

            $dados = is_array($dados) ? $dados : [];

            $agora = time();

            $inicio = (int) (
                $dados['inicio'] ?? $agora
            );

            $tentativas = (int) (
                $dados['tentativas'] ?? 0
            );

            $bloqueadoAte = (int) (
                $dados['bloqueado_ate'] ?? 0
            );

            // VERIFICAR BLOQUEIO EXISTENTE
            if ($bloqueadoAte > $agora) {

                $this->avisarBloqueio(
                    $bloqueadoAte - $agora,
                    $errors
                );

                return null;
            }

            // REINICIAR CONTADOR APÓS O PRAZO
            if (
                $agora - $inicio >= self::JANELA_SEGUNDOS
                || $bloqueadoAte !== 0
            ) {
                $inicio = $agora;
                $tentativas = 0;
            }

            // VERIFICAR CREDENCIAIS
            $usuario = $this->authService->authenticate(
                $email,
                $password
            );

            // LOGIN CORRETO
            if ($usuario !== null) {

                $this->gravarControle($arquivo, [
                    'inicio' => $agora,
                    'tentativas' => 0,
                    'bloqueado_ate' => 0
                ]);

                return $usuario;
            }

            // LOGIN INCORRETO
            $tentativas++;

            if ($tentativas >= self::MAX_TENTATIVAS) {

                $bloqueadoAte =
                    $agora + self::JANELA_SEGUNDOS;

            } else {

                $bloqueadoAte = 0;
            }

            // SALVAR NOVA CONTAGEM
            $this->gravarControle($arquivo, [
                'inicio' => $inicio,
                'tentativas' => $tentativas,
                'bloqueado_ate' => $bloqueadoAte
            ]);

            if ($bloqueadoAte > $agora) {

                $this->avisarBloqueio(
                    $bloqueadoAte - $agora,
                    $errors
                );

            } else {

                $errors[] =
                    'E-mail ou senha incorretos.';
            }

            return null;

        } finally {

            flock($arquivo, LOCK_UN);
            fclose($arquivo);
        }
    }

    // SALVAR CONTROLE DE TENTATIVAS
    private function gravarControle(
        $arquivo,
        array $dados
    ): void {

        $json = json_encode(
            $dados,
            JSON_THROW_ON_ERROR
        );

        rewind($arquivo);

        if (
            !ftruncate($arquivo, 0)
            || fwrite($arquivo, $json) === false
            || !fflush($arquivo)
        ) {
            throw new RuntimeException(
                'Não foi possível salvar o controle de tentativas.'
            );
        }
    }

    // INFORMAR BLOQUEIO TEMPORÁRIO
    private function avisarBloqueio(
        int $segundos,
        array &$errors
    ): void {

        http_response_code(429);

        header(
            'Retry-After: ' . $segundos
        );

        $minutos = (int) ceil($segundos / 60);

        $errors[] =
            'Muitas tentativas de login. '
            . 'Tente novamente em aproximadamente '
            . $minutos
            . ' minuto(s).';
    }

    // ENCERRAR SESSÃO
    public function logout(): void
    {
        Session::destroy();

        header('Location: /login');
        exit;
    }
}
