<?php
use Slim\App;
use App\Controllers\TaskController;

return function (App $app) {
    $taskController = new TaskController();

    // Маршрут создания задачи
    $app->post('/api/tasks', [$taskController, 'createTask']);

    // Маршрут получения задач
    $app->get('/api/tasks', [$taskController, 'getTasks']);

    // Маршрут получения задачи по ID
    $app->get('/api/tasks/{id}', [$taskController, 'getTask']);

    // Маршрут обновления задачи
    $app->put('/api/tasks/{id}', [$taskController, 'updateTask']);

    // Маршрут удаления задачи
    $app->delete('/api/tasks/{id}', [$taskController, 'deleteTask']);
};
