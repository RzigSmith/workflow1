<?php

require_once __DIR__ . "/../core/Model.php";

class Conversation extends Model {
    private $table = "conversation";
    public $type_conversation;
    public $date_creation;

    public function creer() {
        $sql = "INSERT INTO $this->table (type_conversation, date_creation)
            VALUES (:type_conversation, :date_creation)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":type_conversation"=> $this->type_conversation,
            ":date_creation"=> $this->date_creation
        ]);
    }
    public function lister() {
        $sql = "SELECT * FROM $this->table";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}