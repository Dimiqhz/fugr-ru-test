<?php
// app/Middleware/PaginationMiddleware.php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class PaginationMiddleware implements MiddlewareInterface
{
    /**
     * Обрабатывает запрос, проверяя и корректируя параметры пагинации
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        if ($page < 1) {
            $page = 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;
        if ($limit < 1) {
            $limit = 10;
        }
        $queryParams['page'] = $page;
        $queryParams['limit'] = $limit;
        $request = $request->withQueryParams($queryParams);

        return $handler->handle($request);
        }
    }
}