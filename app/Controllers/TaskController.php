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
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Создание задачи",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="priority", type="string"),
     *             @OA\Property(property="category", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка создания задачи"
     *     )
     * )
     */
    public function createTask(Request $request, Response $response): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') === false) {
            $error = ['error' => 'Content-Type должен быть application/json'];
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(415);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = ['error' => 'Неверный формат JSON'];
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        error_log("📌 Данные перед валидацией: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $allowedStatus = ['выполнена', 'не выполнена'];
        $allowedPriority = ['низкий', 'средний', 'высокий'];

        if (!in_array($data['status'] ?? 'не выполнена', $allowedStatus, true)) {
            $error = ['error' => 'Недопустимое значение status'];
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!in_array($data['priority'] ?? 'средний', $allowedPriority, true)) {
            $error = ['error' => 'Недопустимое значение priority'];
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $data['title'] = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $data['description'] = isset($data['description']) ? htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') : null;

        error_log("🟢 SQL на выполнение: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $taskId = $this->taskModel->create($data);
        
        if ($taskId === null) {
            error_log("❌ Ошибка: lastInsertId вернул null");
            $error = ['error' => 'Не удалось создать задачу'];
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $result = ['id' => $taskId, 'message' => 'Task created successfully'];
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Получает список задач с возможностью поиска и сортировки (постраничный вывод присутствует)
     *
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Получение списка задач",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по названию задачи",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Сортировка по due_date или create_date",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы для пагинации",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Количество задач на странице",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список задач",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
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
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     summary="Получение задачи по ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID задачи",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Данные задачи",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Задача не найдена",
     *         @OA\JsonContent(type="object")
     *     )
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
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     summary="Обновление задачи",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID задачи",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="priority", type="string"),
     *             @OA\Property(property="category", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function updateTask(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = (array)$request->getParsedBody();

        $contentType = $request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') === false) {
            $error = ['error' => 'Content-Type должен быть application/json'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(415);
        }

        $validationErrors = \App\Services\Validator::validateTaskData($data, true);
        if (!empty($validationErrors)) {
            $error = ['errors' => $validationErrors];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!$this->taskModel->getById($id)) {
            $error = ['error' => 'Задача не найдена'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if (isset($data['title'])) {
            $data['title'] = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        }
        if (isset($data['description'])) {
            $data['description'] = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
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
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Удаление задачи",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID задачи",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
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
