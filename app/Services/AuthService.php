<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::connection();
    }

    public function register(
        string $name,
        string $email,
        string $password
    ): int {

        $statement =
            $this->db->prepare(
                'SELECT id
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

        $statement->execute([
            'email' => $email,
        ]);

        if ($statement->fetch()) {
            throw new InvalidArgumentException(
                'Já existe uma conta com este e-mail.'
            );
        }

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        $statement =
            $this->db->prepare(
                'INSERT INTO users
                    (name, email, password_hash)
                 VALUES
                    (:name, :email, :password_hash)'
            );

        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int)
            $this->db->lastInsertId();
    }

    public function authenticate(
        string $email,
        string $password
    ): ?array {

        $statement =
            $this->db->prepare(
                'SELECT
                    id,
                    name,
                    email,
                    password_hash
                 FROM users
                 WHERE email = :email
                   AND active = 1
                 LIMIT 1'
            );

        $statement->execute([
            'email' => $email,
        ]);

        $user =
            $statement->fetch();

        if (!$user) {
            return null;
        }

        if (
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }
}