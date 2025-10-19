<?php
// Configuración para el sistema de correos electrónicos
class EmailConfig {
    // Configuración SMTP
    public static $smtp_host = 'smtp.gmail.com'; // Cambiar según el proveedor
    public static $smtp_port = 587;
    public static $smtp_username = 'tu_email@gmail.com'; // Configurar con email real
    public static $smtp_password = 'tu_password_app'; // Usar contraseña de aplicación
    public static $smtp_encryption = 'tls';
    
    // Configuración del remitente
    public static $from_email = 'noreply@doctorapp.com';
    public static $from_name = 'Sistema de Citas Médicas';
    
    // Configuración de la aplicación
    public static $app_name = 'Sistema de Citas Médicas';
    public static $app_url = 'http://localhost/doctor_app'; // Cambiar según la URL del proyecto
}

// Función para enviar emails usando PHPMailer (requiere instalación)
function enviarEmail($destinatario, $asunto, $mensaje, $destinatario_nombre = '') {
    // Verificar si PHPMailer está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Si no está disponible, usar la función mail() de PHP
        return enviarEmailBasico($destinatario, $asunto, $mensaje, $destinatario_nombre);
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = EmailConfig::$smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = EmailConfig::$smtp_username;
        $mail->Password = EmailConfig::$smtp_password;
        $mail->SMTPSecure = EmailConfig::$smtp_encryption;
        $mail->Port = EmailConfig::$smtp_port;
        $mail->CharSet = 'UTF-8';
        
        // Remitente
        $mail->setFrom(EmailConfig::$from_email, EmailConfig::$from_name);
        
        // Destinatario
        $mail->addAddress($destinatario, $destinatario_nombre);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email: " . $e->getMessage());
        return false;
    }
}

// Función básica usando mail() de PHP
function enviarEmailBasico($destinatario, $asunto, $mensaje, $destinatario_nombre = '') {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EmailConfig::$from_name . " <" . EmailConfig::$from_email . ">" . "\r\n";
    
    return mail($destinatario, $asunto, $mensaje, $headers);
}

// Plantillas de email
class EmailTemplates {
    
    public static function nuevaCitaDoctor($cita_data) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nueva Cita Médica Asignada</h2>
                </div>
                <div class='content'>
                    <p>Estimado Dr. {$cita_data['doctor_nombre']} {$cita_data['doctor_apellido']},</p>
                    <p>Se le ha asignado una nueva cita médica. A continuación los detalles:</p>
                    
                    <div class='info-box'>
                        <h4>Información del Paciente</h4>
                        <p><strong>Nombre:</strong> {$cita_data['paciente_nombre']} {$cita_data['paciente_apellido']}</p>
                        <p><strong>Email:</strong> {$cita_data['paciente_email']}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h4>Detalles de la Cita</h4>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($cita_data['fecha_cita'])) . "</p>
                        <p><strong>Hora:</strong> {$cita_data['hora_cita']}</p>
                        <p><strong>Motivo:</strong> {$cita_data['motivo']}</p>
                        " . (!empty($cita_data['sintomas']) ? "<p><strong>Síntomas:</strong> {$cita_data['sintomas']}</p>" : "") . "
                    </div>
                    
                    <p>Por favor, confirme la disponibilidad para esta cita.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático del Sistema de Citas Médicas</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    public static function confirmacionCitaPaciente($cita_data) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .success { color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Confirmación de Cita Médica</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a {$cita_data['paciente_nombre']} {$cita_data['paciente_apellido']},</p>
                    <p class='success'>Su cita médica ha sido confirmada exitosamente.</p>
                    
                    <div class='info-box'>
                        <h4>Detalles de su Cita</h4>
                        <p><strong>Doctor:</strong> Dr. {$cita_data['doctor_nombre']} {$cita_data['doctor_apellido']}</p>
                        <p><strong>Especialidad:</strong> {$cita_data['especialidad']}</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($cita_data['fecha_cita'])) . "</p>
                        <p><strong>Hora:</strong> {$cita_data['hora_cita']}</p>
                        <p><strong>Motivo:</strong> {$cita_data['motivo']}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h4>Instrucciones Importantes</h4>
                        <ul>
                            <li>Llegue 15 minutos antes de su cita</li>
                            <li>Traiga identificación oficial</li>
                            <li>Si necesita cancelar, hágalo con al menos 24 horas de anticipación</li>
                        </ul>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático del Sistema de Citas Médicas</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    public static function resultadosConsulta($cita_data, $resultados) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Resultados de su Consulta Médica</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a {$cita_data['paciente_nombre']} {$cita_data['paciente_apellido']},</p>
                    <p>Adjuntamos los resultados de su consulta médica realizada el " . date('d/m/Y', strtotime($cita_data['fecha_cita'])) . ".</p>
                    
                    <div class='info-box'>
                        <h4>Información de la Consulta</h4>
                        <p><strong>Doctor:</strong> Dr. {$cita_data['doctor_nombre']} {$cita_data['doctor_apellido']}</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($cita_data['fecha_cita'])) . "</p>
                    </div>
                    
                    <div class='info-box'>
                        <h4>Resultados</h4>
                        <p>{$resultados}</p>
                    </div>
                    
                    <p>Si tiene alguna pregunta sobre estos resultados, no dude en contactarnos.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático del Sistema de Citas Médicas</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
}
?>
