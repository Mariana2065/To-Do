<?php
// cron_reminders.php
require_once __DIR__ . '/init.php';

// Selecciona tareas que vencen en las próximas 24 horas
$stmt = $pdo->prepare("
    SELECT t.id, t.title, t.due_date, u.email, u.name
    FROM tasks t
    JOIN users u ON t.assignee_id = u.id
    WHERE t.due_date IS NOT NULL
    AND t.status != 'done'
    AND TIMESTAMPDIFF(HOUR, NOW(), t.due_date) BETWEEN 0 AND 24
");
$stmt->execute();
$tasks = $stmt->fetchAll();

foreach ($tasks as $task) {
    $to = $task['email'];
    $subject = "⏰ Recordatorio de tarea pendiente: " . $task['title'];
    $message = "Hola " . $task['name'] . ",\n\n"
        . "La tarea \"" . $task['title'] . "\" vence el " . $task['due_date'] . ".\n"
        . "Por favor revisa el sistema To-Do.\n\n"
        . APP_NAME;

    // Aquí puedes usar mail() o integrar con PHPMailer
    @mail($to, $subject, $message, "From: " . EMAIL_FROM);
}