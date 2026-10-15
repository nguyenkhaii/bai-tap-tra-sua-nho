<?php
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'trasua';
const DB_USER = 'root';
const DB_PASS = '';
const BASE_URL = '/trasua';
const STATUSES = [
    'pending'   => 'Chờ xác nhận',
    'preparing' => 'Đang pha chế',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã huỷ',
];

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((int)$n, 0, ',', '.') . ' đ'; }
function redirect(string $url): void { header('Location: ' . $url); exit; }

function csrf_field(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_verify(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
    }
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }
function flash_show(): void {
    if (!empty($_SESSION['flash'])) {
        [$msg, $type] = $_SESSION['flash'];
        echo '<div class="flash ' . e($type) . '">' . e($msg) . '</div>';
        unset($_SESSION['flash']);
    }
}

function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void {
    if (!is_admin()) redirect(BASE_URL . '/admin/login.php');
}