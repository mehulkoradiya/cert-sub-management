<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\PDOConnection;

$pdo = PDOConnection::getConnection();

$sql = "
CREATE TABLE IF NOT EXISTS certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS requirement_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certification_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    requirement_type VARCHAR(50) NOT NULL,
    requirement_value INT NOT NULL,
    FOREIGN KEY (certification_id) REFERENCES certifications(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    certification_id INT NOT NULL,
    state VARCHAR(20) NOT NULL,
    type VARCHAR(20) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    auto_renew TINYINT(1) DEFAULT 0,
    FOREIGN KEY (certification_id) REFERENCES certifications(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    duration_hours INT NOT NULL,
    category VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS certification_area_courses (
    certification_id INT NOT NULL,
    area_name VARCHAR(255) NOT NULL,
    course_id INT NOT NULL,
    PRIMARY KEY (certification_id, area_name, course_id),
    FOREIGN KEY (certification_id) REFERENCES certifications(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    echo "Database initialized successfully.\n";
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
