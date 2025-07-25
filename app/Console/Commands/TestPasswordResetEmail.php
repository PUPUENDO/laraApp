<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestPasswordResetEmail extends Command
{
    protected $signature = 'test:password-reset-email {email}';
    protected $description = 'Test password reset email functionality';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado");
            return 1;
        }

        $this->info("Probando envío de email de recuperación de contraseña...");
        $this->info("Email: {$email}");
        $this->info("Usuario: {$user->first_name} {$user->last_name}");
        
        $token = Str::random(64);
        $resetUrl = env('FRONTEND_URL', 'http://localhost:4200') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
        
        $emailData = [
            'user' => $user,
            'token' => $token,
            'resetUrl' => $resetUrl,
            'expiresAt' => Carbon::now()->addHour()->format('Y-m-d H:i:s')
        ];

        $this->info("Token generado: {$token}");
        $this->info("URL de reset: {$resetUrl}");
        $this->info("Configuración de correo actual:");
        $this->info("MAIL_MAILER: " . env('MAIL_MAILER'));
        $this->info("MAIL_HOST: " . env('MAIL_HOST'));
        $this->info("MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS'));

        try {
            Mail::html(
                $this->buildResetEmailTemplate($emailData),
                function ($message) use ($email, $user) {
                    $message->to($email, $user->first_name . ' ' . $user->last_name)
                            ->subject('Recuperación de Contraseña - TEST')
                            ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );
            
            $this->info("✅ Email enviado exitosamente!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error al enviar email: " . $e->getMessage());
            Log::error("Test email error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function buildResetEmailTemplate($data)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Recuperación de Contraseña - TEST</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; }
                .test-banner { background: #f59e0b; color: white; padding: 10px; text-align: center; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="test-banner">
                    🧪 EMAIL DE PRUEBA - TEST ENVIRONMENT
                </div>
                <div class="header">
                    <h1>Recuperación de Contraseña</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . $data['user']->first_name . ' ' . $data['user']->last_name . '</strong>,</p>
                    
                    <p>Este es un email de <strong>PRUEBA</strong> para verificar la funcionalidad de recuperación de contraseña.</p>
                    
                    <p>Token generado: <code>' . $data['token'] . '</code></p>
                    
                    <p>URL de reset:</p>
                    <p style="word-break: break-all; background: #e5e7eb; padding: 10px; border-radius: 5px;">
                        ' . $data['resetUrl'] . '
                    </p>
                    
                    <p><strong>Este enlace expiraría el ' . $data['expiresAt'] . '</strong></p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' ' . config('app.name') . ' - Email de Prueba</p>
                </div>
            </div>
        </body>
        </html>';
    }
}