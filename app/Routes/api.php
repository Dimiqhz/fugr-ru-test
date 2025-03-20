<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\TaskController;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->post('/tasks', TaskController::class . ':createTask');
        $group->get('/tasks', TaskController::class . ':getTasks');
        $group->get('/tasks/{id}', TaskController::class . ':getTask');
        $group->put('/tasks/{id}', TaskController::class . ':updateTask');
        $group->delete('/tasks/{id}', TaskController::class . ':deleteTask');
    });
};
