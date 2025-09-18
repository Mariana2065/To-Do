<?php
require_once '../config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Verificando si el correo electrónico está en la base de datos
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generando un token
        $token = bin2hex(random_bytes(50));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardo el token en la base de datos
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expires_at=?");
        $stmt->execute([$user['id'], $token, $expires_at, $token, $expires_at]);

        // === Inicia la configuración de PHPMailer ===
        $mail = new PHPMailer(true); // El 'true' habilita las excepciones
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // 
            $mail->SMTPAuth = true;
            $mail->Username = 'ToDo.Jacki.Mari@gmail.com'; 
            $mail->Password = 'vdke zvvz qpxl apnk'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Remitente y destinatario
            $mail->setFrom('ToDo.Jacki.Mari@gmail.com', 'To-do'); // Correo electrónico válido y nombre del remitente
            $mail->addAddress($email);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body = "Para restablecer tu contraseña, haz clic en el siguiente enlace: <a href='http://localhost/To-do/public/reset_password.php?token=$token'>Restablecer Contraseña</a>";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Se ha enviado un correo con instrucciones para restablecer tu contraseña.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "No se pudo enviar el correo. Error de Mailer: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico no está registrado.']);
    }
}
?>