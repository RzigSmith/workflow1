<?php
require_once __DIR__ . '/../core/Model.php';

class ChatConversation extends Model {

    public function trouverOuCreer(int $user1, int $user2): int {
        $sql = "SELECT cm1.id_conversation
                FROM conversation_membre cm1
                JOIN conversation_membre cm2 ON cm1.id_conversation = cm2.id_conversation
                JOIN conversation c ON cm1.id_conversation = c.id_conversation
                WHERE cm1.id_user = :u1 AND cm2.id_user = :u2
                  AND c.type_conversation = 'direct'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u1' => $user1, ':u2' => $user2]);
        $row = $stmt->fetchColumn();

        if ($row) return (int) $row;

        // Create new conversation
        $stmt2 = $this->db->prepare(
            "INSERT INTO conversation (type_conversation, date_creation) VALUES ('direct', :now)"
        );
        $stmt2->execute([':now' => date('Y-m-d H:i:s')]);
        $id = (int) $this->db->lastInsertId();

        $stmt3 = $this->db->prepare(
            "INSERT INTO conversation_membre (id_conversation, id_user) VALUES (:c, :u)"
        );
        $stmt3->execute([':c' => $id, ':u' => $user1]);
        $stmt3->execute([':c' => $id, ':u' => $user2]);

        return $id;
    }

    public function getMesConversations(int $userId): array {
        $sql = "SELECT c.id_conversation, c.type_conversation,
                       u.id_user, u.nom, u.prenom, u.username, u.statut_en_ligne,
                       (SELECT contenu FROM chat_message cm2
                        WHERE cm2.id_conversation = c.id_conversation
                        ORDER BY cm2.date_envoi DESC LIMIT 1) AS dernier_message,
                       (SELECT date_envoi FROM chat_message cm3
                        WHERE cm3.id_conversation = c.id_conversation
                        ORDER BY cm3.date_envoi DESC LIMIT 1) AS date_dernier,
                       (SELECT COUNT(*) FROM chat_message cm4
                        WHERE cm4.id_conversation = c.id_conversation
                        AND cm4.id_user != :uid AND cm4.lu = 0) AS messages_non_lus
                FROM conversation_membre cm
                JOIN conversation c ON cm.id_conversation = c.id_conversation
                JOIN conversation_membre cm_other ON cm_other.id_conversation = c.id_conversation
                    AND cm_other.id_user != :uid
                JOIN utilisateur u ON cm_other.id_user = u.id_user
                WHERE cm.id_user = :uid2
                  AND c.type_conversation = 'direct'
                ORDER BY date_dernier DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMessages(int $convId, int $userId, int $limit = 50): array {
        // Verify user is member
        $check = $this->db->prepare(
            "SELECT 1 FROM conversation_membre WHERE id_conversation=:c AND id_user=:u"
        );
        $check->execute([':c' => $convId, ':u' => $userId]);
        if (!$check->fetchColumn()) return [];

        $sql = "SELECT cm.*, u.nom, u.prenom
                FROM chat_message cm
                JOIN utilisateur u ON cm.id_user = u.id_user
                WHERE cm.id_conversation = :c
                ORDER BY cm.date_envoi ASC
                LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':c', $convId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function envoyerMessage(int $convId, int $userId, string $contenu): bool {
        // Verify user is member
        $check = $this->db->prepare(
            "SELECT 1 FROM conversation_membre WHERE id_conversation=:c AND id_user=:u"
        );
        $check->execute([':c' => $convId, ':u' => $userId]);
        if (!$check->fetchColumn()) return false;

        $stmt = $this->db->prepare(
            "INSERT INTO chat_message (id_conversation, id_user, contenu, date_envoi)
             VALUES (:c, :u, :msg, :now)"
        );
        return $stmt->execute([
            ':c'   => $convId,
            ':u'   => $userId,
            ':msg' => $contenu,
            ':now' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getMessagesSince(int $convId, int $userId, int $lastId): array {
        $check = $this->db->prepare(
            "SELECT 1 FROM conversation_membre WHERE id_conversation=:c AND id_user=:u"
        );
        $check->execute([':c' => $convId, ':u' => $userId]);
        if (!$check->fetchColumn()) return [];

        $sql = "SELECT cm.*, u.nom, u.prenom
                FROM chat_message cm
                JOIN utilisateur u ON cm.id_user = u.id_user
                WHERE cm.id_conversation = :c AND cm.id_msg > :last
                ORDER BY cm.date_envoi ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':c' => $convId, ':last' => $lastId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marquerCommeLus(int $convId, int $userId): bool {
        $check = $this->db->prepare(
            "SELECT 1 FROM conversation_membre WHERE id_conversation=:c AND id_user=:u"
        );
        $check->execute([':c' => $convId, ':u' => $userId]);
        if (!$check->fetchColumn()) return false;

        $stmt = $this->db->prepare(
            "UPDATE chat_message SET lu = 1 
             WHERE id_conversation = :c AND id_user != :u AND lu = 0"
        );
        return $stmt->execute([':c' => $convId, ':u' => $userId]);
    }

    public function comptNonLus(int $userId): int {
        $sql = "SELECT COUNT(DISTINCT cm.id_conversation)
                FROM chat_message cm
                JOIN conversation_membre cm_user ON cm_user.id_conversation = cm.id_conversation
                WHERE cm_user.id_user = :uid AND cm.id_user != :uid2 AND cm.lu = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
