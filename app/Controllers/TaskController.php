<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Task;
use App\Config\Database;

class TaskController
{
    private Task $taskModel;

    /**
     * Конструктор контроллера
     */
    public function __construct()
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new \Exception("Ошибка подключения к базе данных");
        }
        $this->taskModel = new Task($db);
    }

    /**
     * Создает новую задачу
     *
     * @SWG\Post(
     *     path="/api/tasks",
     *     summary="Создание задачи",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="title", type="string"),
     *             @SWG\Property(property="description", type="string"),
     *             @SWG\Property(property="due_date", type="string", format="date-time"),
     *             @SWG\Property(property="create_date", type="string", format="date-time"),
     *             @SWG\Property(property="status", type="string"),
     *             @SWG\Property(property="priority", type="string"),
     *             @SWG\Property(property="category", type="string")
     *         )
     *     ),
     *     @SWG\Response(response=201, description="Task created successfully")
     * )
     */
    public function createTask(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        // Валидация
        if (empty($data['title']) || strlen($data['title']) > 255) {
            $error = ['error' => 'Название задачи обязательно и должно быть до 255 символов.'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $data['description'] = $data['description'] ?? '';
        $data['status'] = $data['status'] ?? 'не выполнена';

        $taskId = $this->taskModel->create($data);
        if ($taskId === null) {
            $error = ['error' => 'Не удалось создать задачу'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $result = ['id' => $taskId, 'message' => 'Task created successfully'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Получает список задач с возможностью поиска и сортировки (постраничный вывод присутствует)
     *
     * @SWG\Get(
     *     path="/api/tasks",
     *     summary="Получение списка задач",
     *     @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         type="string",
     *         description="Поиск по названию задачи"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         type="string",
     *         description="Сортировка по due_date или create_date"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         type="integer",
     *         description="Номер страницы для пагинации"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="integer",
     *         description="Количество задач на странице"
     *     ),
     *     @SWG\Response(response=200, description="Список задач")
     * )
     */
    public function getTasks(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $search = $params['search'] ?? null;
        $sort   = $params['sort'] ?? null;
        $page   = isset($params['page']) ? (int)$params['page'] : 1;
        $limit  = isset($params['limit']) ? (int)$params['limit'] : 10;

        $tasks = $this->taskModel->getAll($search, $sort, $page, $limit);
        $response->getBody()->write(json_encode($tasks));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Получает конкретную задачу по ID
     *
     * @SWG\Get(
     *     path="/api/tasks/{id}",
     *     summary="Получение задачи по ID",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         required=true,
     *         description="ID задачи"
     *     ),
     *     @SWG\Response(response=200, description="Данные задачи")
     * )
     */
    public function getTask(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $task = $this->taskModel->getById($id);
        if (!$task) {
            $error = ['error' => 'Задача не найдена'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode($task));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Обновляет задачу по ID
     *
     * @SWG\Put(
     *     path="/api/tasks/{id}",
     *     summary="Обновление задачи",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         required=true,
     *         description="ID задачи"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="title", type="string"),
     *             @SWG\Property(property="description", type="string"),
     *             @SWG\Property(property="due_date", type="string", format="date-time"),
     *             @SWG\Property(property="status", type="string"),
     *             @SWG\Property(property="priority", type="string"),
     *             @SWG\Property(property="category", type="string")
     *         )
     *     ),
     *     @SWG\Response(response=200, description="Task updated successfully")
     * )
     */
    public function updateTask(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = (array)$request->getParsedBody();

        // Проверка на существование задачи
        if (!$this->taskModel->getById($id)) {
            $error = ['error' => 'Задача не найдена'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $success = $this->taskModel->update($id, $data);
        if (!$success) {
            $error = ['error' => 'Не удалось обновить задачу'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        $result = ['message' => 'Task updated successfully'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Удаляет задачу по ID
     *
     * @SWG\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Удаление задачи",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         required=true,
     *         description="ID задачи"
     *     ),
     *     @SWG\Response(response=200, description="Task deleted successfully")
     * )
     */
    public function deleteTask(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        if (!$this->taskModel->getById($id)) {
            $error = ['error' => 'Задача не найдена'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $success = $this->taskModel->delete($id);
        if (!$success) {
            $error = ['error' => 'Не удалось удалить задачу'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        $result = ['message' => 'Task deleted successfully'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
