<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TestPasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:password-reset {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el sistema de recuperación de contraseña enviando una solicitud a la API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!$email) {
            // Buscar el primer usuario disponible
            $user = User::first();
            if (!$user) {
                $this->error('No hay usuarios en la base de datos para probar.');
                return;
            }
            $email = $user->email;
            $this->info("Usando email de prueba: {$email}");
        }

        $this->info('=== PRUEBA DE SISTEMA DE RECUPERACIÓN DE CONTRASEÑA ===');
        $this->info('Email: ' . $email);
        
        try {
            // Hacer solicitud a la API local
            $response = Http::post('http://127.0.0.1:8000/api/forgot-password', [
                'email' => $email
            ]);

            $this->info('Status Code: ' . $response->status());
            $this->info('Response Headers:');
            foreach ($response->headers() as $key => $value) {
                if (in_array($key, ['content-type', 'date'])) {
                    $this->line("  {$key}: " . implode(', ', $value));
                }
            }

            $data = $response->json();
            $this->info('Response Body:');
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            if ($response->successful() && isset($data['success']) && $data['success']) {
                $this->info('✅ ¡Email de recuperación enviado exitosamente!');
                
                if (isset($data['debug_info']['reset_url'])) {
                    $this->info('🔗 URL de reset: ' . $data['debug_info']['reset_url']);
                }
                
                if (env('MAIL_MAILER') === 'log') {
                    $this->warn('📧 Revisa el archivo storage/logs/laravel.log para ver el contenido del email');
                }
            } else {
                $this->error('❌ Error al enviar el email de recuperación');
            }

        } catch (\Exception $e) {
            $this->error('Error al conectar con la API: ' . $e->getMessage());
        }

        $this->info('=== FIN DE LA PRUEBA ===');
    }
}
