<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class RepublicService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findActiveByUser(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                r.id,
                r.name,
                r.description,
                r.invite_code,
                rm.role
             FROM republic_members rm
             INNER JOIN republics r
                ON r.id = rm.republic_id
             WHERE rm.user_id = :user_id
               AND rm.active = 1
               AND r.active = 1
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);
        $republic = $statement->fetch();

        if (!$republic) {
            return null;
        }

        return [
            'id' => (int) $republic['id'],
            'name' => $republic['name'],
            'description' => $republic['description'],
            'invite_code' => $republic['invite_code'],
            'role' => $republic['role'],
        ];
    }

    public function create(int $userId, string $name): array
    {
        $name = trim($name);
        $this->validateName($name);

        if ($this->findActiveByUser($userId) !== null) {
            throw new InvalidArgumentException(
                'Você já participa de uma república.'
            );
        }

        $inviteCode = $this->generateInviteCode();

        try {
            $this->db->beginTransaction();

            $statement = $this->db->prepare(
                'INSERT INTO republics
                    (name, invite_code, created_by)
                 VALUES
                    (:name, :invite_code, :created_by)'
            );

            $statement->execute([
                'name' => $name,
                'invite_code' => $inviteCode,
                'created_by' => $userId,
            ]);

            $republicId = (int) $this->db->lastInsertId();

            $statement = $this->db->prepare(
                'INSERT INTO republic_members
                    (republic_id, user_id, role)
                 VALUES
                    (:republic_id, :user_id, :role)'
            );

            $statement->execute([
                'republic_id' => $republicId,
                'user_id' => $userId,
                'role' => 'admin',
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'Não foi possível criar a república.',
                0,
                $exception
            );
        }

        return [
            'id' => $republicId,
            'name' => $name,
            'description' => null,
            'invite_code' => $inviteCode,
            'role' => 'admin',
        ];
    }

    public function join(int $userId, string $inviteCode): array
    {
        $inviteCode = strtoupper(trim($inviteCode));

        if ($inviteCode === '') {
            throw new InvalidArgumentException(
                'Informe o código da república.'
            );
        }

        if ($this->findActiveByUser($userId) !== null) {
            throw new InvalidArgumentException(
                'Você já participa de uma república.'
            );
        }

        $statement = $this->db->prepare(
            'SELECT id, name, description, invite_code
             FROM republics
             WHERE invite_code = :invite_code
               AND active = 1
             LIMIT 1'
        );

        $statement->execute(['invite_code' => $inviteCode]);
        $republic = $statement->fetch();

        if (!$republic) {
            throw new InvalidArgumentException(
                'Código de convite inválido.'
            );
        }

        $statement = $this->db->prepare(
            'SELECT id
             FROM republic_members
             WHERE republic_id = :republic_id
               AND user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'republic_id' => $republic['id'],
            'user_id' => $userId,
        ]);

        $existing = $statement->fetch();

        if ($existing) {
            $statement = $this->db->prepare(
                'UPDATE republic_members
                 SET active = 1, role = :role
                 WHERE id = :id'
            );

            $statement->execute([
                'role' => 'member',
                'id' => $existing['id'],
            ]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO republic_members
                    (republic_id, user_id, role)
                 VALUES
                    (:republic_id, :user_id, :role)'
            );

            $statement->execute([
                'republic_id' => $republic['id'],
                'user_id' => $userId,
                'role' => 'member',
            ]);
        }

        return [
            'id' => (int) $republic['id'],
            'name' => $republic['name'],
            'description' => $republic['description'],
            'invite_code' => $republic['invite_code'],
            'role' => 'member',
        ];
    }

    public function updateSettings(
        int $republicId,
        int $userId,
        string $name,
        string $description
    ): array {
        $this->requireAdmin($republicId, $userId);

        $name = trim($name);
        $description = trim($description);

        $this->validateName($name);

        if (strlen($description) > 255) {
            throw new InvalidArgumentException(
                'A descrição deve possuir no máximo 255 caracteres.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE republics
             SET name = :name,
                 description = :description
             WHERE id = :id
               AND active = 1'
        );

        $statement->execute([
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'id' => $republicId,
        ]);

        $republic = $this->findActiveByUser($userId);

        if ($republic === null) {
            throw new RuntimeException(
                'Não foi possível atualizar os dados da república.'
            );
        }

        return $republic;
    }

    private function validateName(string $name): void
    {
        if (strlen($name) < 3) {
            throw new InvalidArgumentException(
                'O nome da república deve ter pelo menos 3 caracteres.'
            );
        }

        if (strlen($name) > 120) {
            throw new InvalidArgumentException(
                'O nome da república é muito longo.'
            );
        }
    }

    private function requireAdmin(int $republicId, int $userId): void
    {
        $statement = $this->db->prepare(
            'SELECT id
             FROM republic_members
             WHERE republic_id = :republic_id
               AND user_id = :user_id
               AND role = "admin"
               AND active = 1
             LIMIT 1'
        );

        $statement->execute([
            'republic_id' => $republicId,
            'user_id' => $userId,
        ]);

        if (!$statement->fetch()) {
            throw new InvalidArgumentException(
                'Você não possui permissão para alterar a república.'
            );
        }
    }

    private function generateInviteCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));

            $statement = $this->db->prepare(
                'SELECT id
                 FROM republics
                 WHERE invite_code = :code
                 LIMIT 1'
            );

            $statement->execute(['code' => $code]);
        } while ($statement->fetch());

        return $code;
    }
}
