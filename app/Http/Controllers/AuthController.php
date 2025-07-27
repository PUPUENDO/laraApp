<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'required|string|email|unique:users',
            'password'   => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'phone'      => $validated['phone'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener todos los usuarios registrados.
     */
    public function getAllUsers()
    {
        $users = User::select('id', 'first_name', 'last_name', 'email', 'phone')
            ->orderBy('first_name')
            ->get();

        return response()->json($users);
    }

    /**
     * Obtener usuarios disponibles para agregar a un equipo específico.
     */
    public function getAvailableUsers(string $teamId)
    {
        $team = Team::findOrFail($teamId);

        // Verificar que el usuario autenticado sea miembro del equipo
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            return response()->json([
                'success' => false, 
                'error' => 'No tienes permisos para ver los usuarios disponibles para este equipo'
            ], 403);
        }

        // Obtener usuarios que NO están en el equipo
        $availableUsers = User::select('id', 'first_name', 'last_name', 'email')
            ->whereNotIn('id', function($query) use ($teamId) {
                $query->select('user_id')
                      ->from('team_user')
                      ->where('team_id', $teamId);
            })
            ->orderBy('first_name')
            ->get();

        return response()->json($availableUsers);
    }

    /**
     * Enviar enlace de recuperación de contraseña.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un usuario con ese email'
                ], 404);
            }

            // Generar token único
            $token = Str::random(64);

            // Eliminar tokens anteriores para este email
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            // Crear nuevo token
            DB::table('password_reset_tokens')->insert([
                'email' => $validated['email'],
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]);

            // Crear URL de reset
            $resetUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . urlencode($validated['email']);

            try {
                // Intentar enviar el email
                $emailData = [
                    'user' => $user,
                    'token' => $token,
                    'resetUrl' => $resetUrl,
                    'expiresAt' => Carbon::now()->addHour()->format('Y-m-d H:i:s')
                ];

                // Registrar información en logs para debugging
                Log::info('=== PASSWORD RESET EMAIL ===');
                Log::info('Para: ' . $validated['email']);
                Log::info('Usuario: ' . $user->first_name . ' ' . $user->last_name);
                Log::info('Token: ' . $token);
                Log::info('URL de Reset: ' . $resetUrl);
                Log::info('Expira: ' . $emailData['expiresAt']);
                Log::info('MAIL_MAILER: ' . env('MAIL_MAILER'));
                Log::info('MAIL_HOST: ' . env('MAIL_HOST'));
                Log::info('MAIL_FROM_ADDRESS: ' . env('MAIL_FROM_ADDRESS'));
                Log::info('=============================');

                // Verificar configuración de correo
                $mailConfig = config('mail.default');
                Log::info('Mail config driver: ' . $mailConfig);
                
                // Enviar email usando la configuración del .env
                try {
                    Mail::html(
                        $this->buildResetEmailTemplate($emailData),
                        function ($message) use ($validated, $user) {
                            $message->to($validated['email'], $user->first_name . ' ' . $user->last_name)
                                    ->subject('Recuperación de Contraseña - Laravel SMTP')
                                    ->from(config('mail.from.address'), 'Laravel SMTP');
                        }
                    );
                    
                    $emailSent = true;
                    Log::info('Email enviado exitosamente');
                    
                } catch (\Exception $mailSendException) {
                    Log::error('Error específico al enviar email: ' . $mailSendException->getMessage());
                    throw $mailSendException;
                }

                if ($emailSent) {
                    Log::info('Password reset email sent successfully to: ' . $validated['email']);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Email sent successfully'
                    ];

                    // Solo para desarrollo - incluir datos para testing
                    if (env('APP_ENV') === 'local' || env('APP_DEBUG')) {
                        $response['debug_info'] = [
                            'reset_token' => $token,
                            'reset_url' => $resetUrl,
                            'mail_driver' => env('MAIL_MAILER'),
                            'expires_at' => $emailData['expiresAt']
                        ];
                    }

                    return response()->json($response);
                }

            } catch (\Exception $mailException) {
                Log::error('Error sending password reset email: ' . $mailException->getMessage());
                Log::error('Stack trace: ' . $mailException->getTraceAsString());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el email de recuperación',
                    'error_details' => env('APP_DEBUG') ? $mailException->getMessage() : 'Error interno del servidor'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in forgot password: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in forgot password: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud',
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Restablecer contraseña con token.
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed'
            ]);

            // Buscar el token
            $tokenData = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de recuperación no válido'
                ], 400);
            }

            // Verificar que el token no haya expirado (1 hora)
            if (Carbon::parse($tokenData->created_at)->addHour()->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El token de recuperación ha expirado'
                ], 400);
            }

            // Verificar el token
            if (!Hash::check($validated['token'], $tokenData->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de recuperación no válido'
                ], 400);
            }

            // Actualizar la contraseña
            $user = User::where('email', $validated['email'])->first();
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Eliminar el token usado
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al restablecer la contraseña'
            ], 500);
        }
    }

    /**
     * Construir el template de email para reset de contraseña.
     */
    private function buildResetEmailTemplate($data)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Recuperación de Contraseña</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . $data['user']->first_name . ' ' . $data['user']->last_name . '</strong>,</p>
                    
                    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
                    
                    <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                    
                    <p style="text-align: center;">
                        <a href="' . $data['resetUrl'] . '" class="button">Restablecer Contraseña</a>
                    </p>
                    
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style="word-break: break-all; background: #e5e7eb; padding: 10px; border-radius: 5px;">
                        ' . $data['resetUrl'] . '
                    </p>
                    
                    <p><strong>Este enlace expirará el ' . $data['expiresAt'] . '</strong></p>
                    
                    <p>Si no solicitaste este restablecimiento, puedes ignorar este email. Tu contraseña no será cambiada.</p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' ' . config('app.name') . '. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
