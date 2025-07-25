<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border: 1px solid #e9ecef;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .token-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .warning {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ env('APP_NAME', 'LaraApp') }}</h1>
        <h2>Recuperación de Contraseña</h2>
    </div>
    
    <div class="content">
        <h3>Hola {{ $user->first_name }} {{ $user->last_name }},</h3>
        
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
        
        <p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a>
        </div>
        
        <div class="token-info">
            <strong>Información importante:</strong>
            <ul>
                <li>Este enlace expirará el <strong>{{ $expiresAt }}</strong></li>
                <li>Si no solicitaste este restablecimiento, puedes ignorar este email</li>
                <li>Tu contraseña actual seguirá siendo válida hasta que la cambies</li>
            </ul>
        </div>
        
        <div class="warning">
            <strong>⚠️ Aviso de Seguridad:</strong><br>
            Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:<br>
            <small>{{ $resetUrl }}</small>
        </div>
        
        <p>Si tienes problemas para restablecer tu contraseña, contacta con nuestro equipo de soporte.</p>
        
        <p>Saludos,<br>
        El equipo de {{ env('APP_NAME', 'LaraApp') }}</p>
    </div>
    
    <div class="footer">
        <p>Este es un email automático, por favor no respondas a este mensaje.</p>
        <p>&copy; {{ date('Y') }} {{ env('APP_NAME', 'LaraApp') }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>
