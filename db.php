<?php
$config = require 'config.php';
$dbConf = $config['mysql'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($dbConf['host'], $dbConf['user'], $dbConf['pass']);
$conn->set_charset('utf8mb4');

$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbConf['db']}`");
$conn->select_db($dbConf['db']);

$conn->query("
    CREATE TABLE IF NOT EXISTS `{$dbConf['table']}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45),
        device_type VARCHAR(32),
        operating_system VARCHAR(64),
        browser_version TEXT,
        gpu TEXT,
        screen_resolution VARCHAR(16),
        platform VARCHAR(64),
        referrer TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

function insertScanData($conn, $tableName, $data) {
    $stmt = $conn->prepare("
        INSERT INTO `$tableName` (
            ip_address, device_type, operating_system, browser_version,
            gpu, screen_resolution, platform, referrer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssss",
        $data['ip_address'],
        $data['device_type'],
        $data['operating_system'],
        $data['browser_version'],
        $data['gpu'],
        $data['screen_resolution'],
        $data['platform'],
        $data['referrer']
    );

    $stmt->execute();
    $stmt->close();
}