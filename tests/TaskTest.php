<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Models\Task;

class TaskTest extends TestCase
{
    private PDO $pdo;
    private Task $taskModel;

    /**
     * Метод setUp выполняется перед каждым тестом
     * Создаётся in-memory база данных SQLite и создаётся таблица tasks
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Создание схемы таблицы tasks
        $this->pdo->exec("
            CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                due_date DATETIME,
                create_date DATETIME,
                status VARCHAR(50),
                priority VARCHAR(50),
                category VARCHAR(100)
            );
        ");

        $this->taskModel = new Task($this->pdo);
    }

    /**
     * Тест создания задачи.
     */
    public function testCreateTask(): void
    {
        $data = [
            'title'       => 'Test Task',
            'description' => 'Test description',
            'due_date'    => '2025-01-20 15:00:00',
            'create_date' => '2025-01-20 15:00:00',
            'status'      => 'не выполнена',
            'priority'    => 'высокий',
            'category'    => 'Работа'
        ];

        $taskId = $this->taskModel->create($data);
        $this->assertIsInt($taskId, "Task ID должен быть целым числом");

        $task = $this->taskModel->getById($taskId);
        $this->assertNotNull($task, "Созданная задача должна быть получена");
        $this->assertEquals($data['title'], $task['title'], "Название задачи должно совпадать");
    }

    /**
     * Тест получения списка задач с поиском и сортировкой.
     */
    public function testGetAllTasks(): void
    {
        $data1 = [
            'title'       => 'Task One',
            'description' => 'Desc One',
            'due_date'    => '2025-01-20 15:00:00',
            'create_date' => '2025-01-20 15:00:00',
            'status'      => 'не выполнена',
            'priority'    => 'средний',
            'category'    => 'Работа'
        ];
        $data2 = [
            'title'       => 'Task Two',
            'description' => 'Desc Two',
            'due_date'    => '2025-01-21 15:00:00',
            'create_date' => '2025-01-21 15:00:00',
            'status'      => 'не выполнена',
            'priority'    => 'низкий',
            'category'    => 'Дом'
        ];
        $this->taskModel->create($data1);
        $this->taskModel->create($data2);

        $tasks = $this->taskModel->getAll(null, 'due_date', 1, 10);
        $this->assertCount(2, $tasks, "Должно быть 2 задачи");

        // Проверяем поиск по названию
        $tasksSearch = $this->taskModel->getAll('One', null, 1, 10);
        $this->assertCount(1, $tasksSearch, "Должна быть 1 задача с 'One' в названии");
    }

    /**
     * Тест обновления задачи.
     */
    public function testUpdateTask(): void
    {
        $data = [
            'title'       => 'Original Task',
            'description' => 'Original description',
            'due_date'    => '2025-01-20 15:00:00',
            'create_date' => '2025-01-20 15:00:00',
            'status'      => 'не выполнена',
            'priority'    => 'средний',
            'category'    => 'Личное'
        ];
        $taskId = $this->taskModel->create($data);

        $updateData = [
            'title'       => 'Updated Task',
            'description' => 'Updated description',
            'due_date'    => '2025-01-25 18:00:00',
            'status'      => 'выполнена',
            'priority'    => 'высокий',
            'category'    => 'Работа'
        ];
        $updateResult = $this->taskModel->update($taskId, $updateData);
        $this->assertTrue($updateResult, "Обновление задачи должно вернуть true");

        $updatedTask = $this->taskModel->getById($taskId);
        $this->assertEquals($updateData['title'], $updatedTask['title'], "Название задачи должно обновиться");
        $this->assertEquals($updateData['status'], $updatedTask['status'], "Статус задачи должен обновиться");
    }

    /**
     * Тест удаления задачи.
     */
    public function testDeleteTask(): void
    {
        $data = [
            'title'       => 'Task to Delete',
            'description' => 'Description',
            'due_date'    => '2025-01-20 15:00:00',
            'create_date' => '2025-01-20 15:00:00',
            'status'      => 'не выполнена',
            'priority'    => 'низкий',
            'category'    => 'Дом'
        ];
        $taskId = $this->taskModel->create($data);

        $task = $this->taskModel->getById($taskId);
        $this->assertNotNull($task, "Задача должна существовать перед удалением");
        $deleteResult = $this->taskModel->delete($taskId);
        $this->assertTrue($deleteResult, "Удаление задачи должно вернуть true");

        $deletedTask = $this->taskModel->getById($taskId);
        $this->assertNull($deletedTask, "После удаления задача не должна быть найдена");
    }
}
