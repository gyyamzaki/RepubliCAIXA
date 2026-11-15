<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class ProfileService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, phone, created_at
             FROM users
             WHERE id = :id
               AND active = 1
             LIMIT 1'
        );

        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        if (!$user) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at'],
        ];
    }

    public function updateProfile(
        int $userId,
        string $name,
        string $email,
        string $phone
    ): array {
        $name = trim($name);
        $email = strtolower(trim($email));
        $phone = trim($phone);

        if (strlen($name) < 2 || strlen($name) > 120) {
            throw new InvalidArgumentException(
                'Informe um nome entre 2 e 120 caracteres.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Informe um e-mail válido.'
            );
        }

        if (strlen($email) > 180) {
            throw new InvalidArgumentException(
                'O e-mail informado é muito longo.'
            );
        }

        if ($phone !== '' && strlen($phone) > 30) {
            throw new InvalidArgumentException(
                'O telefone informado é muito longo.'
            );
        }

        $statement = $this->db->prepare(
            'SELECT id
             FROM users
             WHERE email = :email
               AND id <> :id
             LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
            'id' => $userId,
        ]);

        if ($statement->fetch()) {
            throw new InvalidArgumentException(
                'Este e-mail já está sendo utilizado por outra conta.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone
             WHERE id = :id
               AND active = 1'
        );

        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'id' => $userId,
        ]);

        $updated = $this->findById($userId);

        if ($updated === null) {
            throw new InvalidArgumentException(
                'Não foi possível atualizar o perfil.'
            );
        }

        return $updated;
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException(
                'A nova senha deve possuir pelo menos 8 caracteres.'
            );
        }

        $statement = $this->db->prepare(
            'SELECT password_hash
             FROM users
             WHERE id = :id
               AND active = 1
             LIMIT 1'
        );

        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            throw new InvalidArgumentException(
                'A senha atual está incorreta.'
            );
        }

        if (password_verify($newPassword, $user['password_hash'])) {
            throw new InvalidArgumentException(
                'A nova senha deve ser diferente da senha atual.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE users
             SET password_hash = :password_hash
             WHERE id = :id'
        );

        $statement->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);
    }
}
