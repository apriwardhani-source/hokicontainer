<?php
class DBSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['data'] : '';
    }

    public function write($id, $data): bool {
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data, last_access) VALUES (?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$id, $data]);
    }

    public function destroy($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($maxlifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        return $stmt->execute([$maxlifetime]) ? 1 : false;
    }
}
?>
