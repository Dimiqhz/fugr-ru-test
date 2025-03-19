<?php
namespace App\Services;

class Validator
{
    /**
     * Валидирует данные задачи
     */
    public static function validateTaskData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['title'])) {
            if (!isset($data['title']) || empty(trim($data['title']))) {
                $errors['title'] = 'Название задачи обязательно';
            } elseif (mb_strlen($data['title']) > 255) {
                $errors['title'] = 'Название задачи должно быть до 255 символов';
            }
        }

        if (isset($data['due_date'])) {
            if (!self::validateDateTime($data['due_date'])) {
                $errors['due_date'] = 'Неверный формат даты выполнения. Ожидается корректная дата';
            }
        } elseif (!$isUpdate) {
            $errors['due_date'] = 'Срок выполнения задачи обязателен';
        }

        if (isset($data['create_date'])) {
            if (!self::validateDateTime($data['create_date'])) {
                $errors['create_date'] = 'Неверный формат даты создания. Ожидается корректная дата';
            }
        } elseif (!$isUpdate) {
            $errors['create_date'] = 'Дата создания задачи обязательна';
        }

        if (isset($data['status'])) {
            $allowedStatus = ['выполнена', 'не выполнена'];
            if (!in_array($data['status'], $allowedStatus, true)) {
                $errors['status'] = 'Статус задачи должен быть "выполнена" или "не выполнена"';
            }
        }
        if (isset($data['priority'])) {
            $allowedPriority = ['низкий', 'средний', 'высокий'];
            if (!in_array($data['priority'], $allowedPriority, true)) {
                $errors['priority'] = 'Приоритет задачи должен быть "низкий", "средний" или "высокий"';
            }
        }
        if (isset($data['category']) && !is_string($data['category'])) {
            $errors['category'] = 'Категория задачи должна быть строкой';
        }

        return $errors;
    }

    /**
     * Проверяет, является ли строка корректной датой
     */
    public static function validateDateTime(string $dateStr): bool
    {
        $date = date_create($dateStr);
        return $date !== false;
    }
}
