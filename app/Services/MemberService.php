<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class MemberService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function listMembers(
        int $republicId
    ): array {
        $statement = $this->db->prepare(
            'SELECT
                rm.id AS member_id,
                rm.user_id,
                rm.role,
                rm.joined_at,
                u.name,
                u.email
             FROM republic_members rm
             INNER JOIN users u
                ON u.id = rm.user_id
             WHERE rm.republic_id = :republic_id
               AND rm.active = 1
               AND u.active = 1
             ORDER BY
                CASE
                    WHEN rm.role = "admin"
                    THEN 0
                    ELSE 1
                END,
                u.name ASC'
        );

        $statement->execute([
            'republic_id' => $republicId,
        ]);

        return $statement->fetchAll();
    }

    public function changeRole(
        int $republicId,
        int $requesterUserId,
        int $memberId,
        string $newRole
    ): void {
        if (
            !in_array(
                $newRole,
                ['admin', 'member'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Função inválida.'
            );
        }

        $this->requireAdmin(
            $republicId,
            $requesterUserId
        );

        $member = $this->findMember(
            $republicId,
            $memberId
        );

        if ($member === null) {
            throw new InvalidArgumentException(
                'Morador não encontrado.'
            );
        }

        if (
            (int) $member['user_id']
            === $requesterUserId
            && $newRole !== 'admin'
        ) {
            throw new InvalidArgumentException(
                'Você não pode remover sua própria função de administrador.'
            );
        }

        $statement = $this->db->prepare(
            'UPDATE republic_members
             SET role = :role
             WHERE id = :member_id
               AND republic_id = :republic_id'
        );

        $statement->execute([
            'role' => $newRole,
            'member_id' => $memberId,
            'republic_id' => $republicId,
        ]);
    }

    public function removeMember(
        int $republicId,
        int $requesterUserId,
        int $memberId
    ): void {
        $this->requireAdmin(
            $republicId,
            $requesterUserId
        );

        $member = $this->findMember(
            $republicId,
            $memberId
        );

        if ($member === null) {
            throw new InvalidArgumentException(
                'Morador não encontrado.'
            );
        }

        if (
            (int) $member['user_id']
            === $requesterUserId
        ) {
            throw new InvalidArgumentException(
                'Você não pode remover a si mesmo da república.'
            );
        }

        /*
         * Não deletamos o registro.
         * Apenas desativamos para preservar histórico.
         */
        $statement = $this->db->prepare(
            'UPDATE republic_members
             SET active = 0
             WHERE id = :member_id
               AND republic_id = :republic_id'
        );

        $statement->execute([
            'member_id' => $memberId,
            'republic_id' => $republicId,
        ]);
    }

    private function requireAdmin(
        int $republicId,
        int $userId
    ): void {
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
                'Você não possui permissão para realizar esta ação.'
            );
        }
    }

    private function findMember(
        int $republicId,
        int $memberId
    ): ?array {
        $statement = $this->db->prepare(
            'SELECT
                id,
                user_id,
                role
             FROM republic_members
             WHERE id = :member_id
               AND republic_id = :republic_id
               AND active = 1
             LIMIT 1'
        );

        $statement->execute([
            'member_id' => $memberId,
            'republic_id' => $republicId,
        ]);

        $member = $statement->fetch();

        return $member ?: null;
    }
}