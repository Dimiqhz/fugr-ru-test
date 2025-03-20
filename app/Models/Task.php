<?php
namespace App\Models;

use PDO;
use PDOException;

class Task
{
    private PDO $db;

    /**
     * Конструктор класса Task
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Создает новую задачу в базе данных
     */
    public function create(array $data): ?int
    {
        if (isset($data['status'])) {
            $data['status'] = trim($data['status']);
            error_log("Статус (обычный): " . $data['status']);
            error_log("Статус (hex): " . bin2hex($data['status']));
        }
        if (isset($data['priority'])) {
            $data['priority'] = trim($data['priority']);
        }

        $allowedStatuses = ['выполнена', 'не выполнена'];
        if (!in_array($data['status'], $allowedStatuses, true)) {
            error_log("❌ Ошибка: Неверное значение 'status': " . $data['status']);
            return null;
        }

        $sql = "INSERT INTO tasks (title, description, due_date, status, priority, category)
                VALUES (:title, :description, :due_date, :status, :priority, :category)";

        try {
            error_log("🟢 SQL на выполнение: " . json_encode($data, JSON_UNESCAPED_UNICODE));

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':due_date', $data['due_date'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindValue(':priority', $data['priority'], PDO::PARAM_STR);
            $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
            
            $stmt->execute();

            $lastId = $this->db->lastInsertId();
            error_log("🟢 SQL выполнен, LAST_INSERT_ID: " . $lastId);

            if (!$lastId) {
                throw new \Exception("❌ Ошибка: lastInsertId вернул null.");
            }

            return (int)$lastId;
        } catch (PDOException $e) {
            error_log("❌ Ошибка SQL: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получает список задач с возможностью поиска, сортировки и вывода
     */
    public function getAll(?string $search, ?string $sort, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM tasks";
        $params = [];

        if ($search) {
            $sql .= " WHERE title LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        if ($sort && in_array($sort, ['due_date', 'create_date'])) {
            $sql .= " ORDER BY $sort";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Ошибка получения задачи: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получает задачу по ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM tasks WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            return $task ? $task : null;
        } catch (PDOException $e) {
            error_log('Ошибка получения задачи: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновляет данные задачи
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description', 'due_date', 'status', 'priority', 'category'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        if (empty($fields)) {
            return false;
        }
        $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Ошибка обновления задачи: ' . $e->getMessage());
            return false;
        }
    }

    /*
     * Удаляет задачу по ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tasks WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Ошибка удаления задачи: ' . $e->getMessage());
            return false;
        }
    }
}
