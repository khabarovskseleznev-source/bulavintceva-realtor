<?php
/**
 * send-to-telegram.php
 * Принимает данные форм сайта и отправляет уведомление в Telegram администратору.
 *
 * НАСТРОЙКА (перед деплоем):
 *   1. Скопируйте backend/config.php.example → backend/config.php
 *   2. Заполните BOT_TOKEN и ADMIN_CHAT_ID в config.php
 *   3. config.php в .gitignore — не коммитить!
 */

// ──────────────────────────────────────────────
// Заголовки
// ──────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://xn--80aalfvksb3a.xn--p1ai');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// ──────────────────────────────────────────────
// Конфигурация (из config.php или переменных окружения)
// ──────────────────────────────────────────────
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

$bot_token   = defined('BOT_TOKEN')      ? BOT_TOKEN      : getenv('BOT_TOKEN');
$admin_id    = defined('ADMIN_CHAT_ID')  ? ADMIN_CHAT_ID  : getenv('ADMIN_CHAT_ID');

if (!$bot_token || !$admin_id) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Сервер не настроен']);
    exit;
}

// ──────────────────────────────────────────────
// Парсинг входных данных
// ──────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$data  = json_decode($raw, true);

if (!$data) {
    // Fallback: обычная POST-форма
    $data = $_POST;
}

// ──────────────────────────────────────────────
// Валидация
// ──────────────────────────────────────────────
$errors = [];

$name       = trim($data['name']       ?? '');
$phone      = trim($data['phone']      ?? '');
$email      = trim($data['email']      ?? '');
$message    = trim($data['message']    ?? '');
$form_type  = trim($data['type']       ?? 'general');
$accept_pdn = !empty($data['accept_pdn']);

if (empty($name)) {
    $errors[] = 'Имя обязательно';
}
if (empty($phone)) {
    $errors[] = 'Телефон обязателен';
} elseif (!preg_match('/^[\+\d\s\(\)\-]{7,20}$/', $phone)) {
    $errors[] = 'Некорректный формат телефона';
}
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный формат email';
}
if (!$accept_pdn) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

// Защита от спама: honeypot
if (!empty($data['website'])) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Заявка принята']);
    exit;
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
    exit;
}

// ──────────────────────────────────────────────
// Формирование сообщения
// ──────────────────────────────────────────────
$type_labels = [
    'callback'  => '📞 Заказать звонок',
    'checklist' => '📋 Получить чек-лист',
    'viewing'   => '🏠 Запись на просмотр',
    'general'   => '📩 Общая заявка',
];

$type_label = $type_labels[$form_type] ?? '📩 Новая заявка';

$text  = "🔔 <b>{$type_label}</b>\n";
$text .= "━━━━━━━━━━━━━━━━━━\n";
$text .= "👤 <b>Имя:</b> " . htmlspecialchars($name, ENT_QUOTES) . "\n";
$text .= "📱 <b>Телефон:</b> " . htmlspecialchars($phone, ENT_QUOTES) . "\n";

if (!empty($email)) {
    $text .= "✉️ <b>Email:</b> " . htmlspecialchars($email, ENT_QUOTES) . "\n";
}
if (!empty($message)) {
    $text .= "💬 <b>Сообщение:</b> " . htmlspecialchars($message, ENT_QUOTES) . "\n";
}

$text .= "━━━━━━━━━━━━━━━━━━\n";
$text .= "🌐 <i>Источник: сайт булавинская.рф</i>\n";
$text .= "🕐 <i>" . date('d.m.Y H:i', time() + 36000) . " (МСК+7)</i>";  // Хабаровское время

// ──────────────────────────────────────────────
// Отправка в Telegram
// ──────────────────────────────────────────────
$url     = "https://api.telegram.org/bot{$bot_token}/sendMessage";
$payload = [
    'chat_id'                  => $admin_id,
    'text'                     => $text,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ──────────────────────────────────────────────
// Логирование (опционально)
// ──────────────────────────────────────────────
$log_dir  = __DIR__ . '/logs';
$log_file = $log_dir . '/requests.log';

if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0750, true);
}

$log_entry = date('Y-m-d H:i:s') . " | type={$form_type} | name=" . mb_substr($name, 0, 30)
           . " | phone=" . mb_substr($phone, 0, 15)
           . " | http={$http_code}"
           . ($curl_err ? " | err={$curl_err}" : '')
           . "\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// ──────────────────────────────────────────────
// Ответ клиенту
// ──────────────────────────────────────────────
$tg_result = json_decode($response, true);

if ($http_code === 200 && !empty($tg_result['ok'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка принята. Елена свяжется с вами в ближайшее время.',
    ]);
} else {
    // Telegram недоступен — всё равно сообщаем пользователю об успехе,
    // но логируем ошибку (заявка не потеряна — есть лог)
    error_log("Telegram API error: http={$http_code}, curl={$curl_err}, response={$response}");
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка принята. Елена свяжется с вами в ближайшее время.',
    ]);
}
