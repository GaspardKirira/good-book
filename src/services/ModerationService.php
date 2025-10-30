<?php

namespace Softadastra\Services;

use PDO;

class ModerationService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crée un signalement de modération.
     *
     * @param string $type     Type de signalement : product|chat|review|order|user
     * @param string $title    Titre court affiché dans le dashboard admin
     * @param string $userRef  Référence de l’utilisateur ex: "seller:#392"
     * @param string $severity Niveau : low|medium|high
     * @param array|null $payload Détails JSON (id produit, message, raison, etc.)
     * @return int ID du signalement créé
     */
    public function flag(
        string $type,
        string $title,
        string $userRef,
        string $severity = 'low',
        ?array $payload = null
    ): int {
        $sql = "INSERT INTO moderation_queue (type, title, user_ref, severity, payload_json)
                VALUES (:type, :title, :user_ref, :severity, :payload_json)";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':type'        => $type,
            ':title'       => $title,
            ':user_ref'    => $userRef,
            ':severity'    => $severity,
            ':payload_json' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int)$this->db->lastInsertId();
    }
}
