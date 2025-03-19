CREATE DATABASE IF NOT EXISTS tasks_db;
USE tasks_db;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    due_date DATETIME NOT NULL,
    create_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('выполнена', 'не выполнена') NOT NULL DEFAULT 'не выполнена',
    priority ENUM('низкий', 'средний', 'высокий') NOT NULL DEFAULT 'средний',
    category VARCHAR(100) NULL
);