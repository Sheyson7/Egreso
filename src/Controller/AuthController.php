<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Identity\AuthenticationService;
use DomainException;
use Throwable;

final class AuthController
{
    public function __construct(private ?AuthenticationService $authentication) {}

    public function handle(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respondError('Método no permitido.', 405, '/cuenta.php');
        }
        \require_csrf();

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            $this->respondSuccess('Cerraste sesión correctamente.', '/');
        }

        try {
            if ($action === 'register') {
                $user = $this->authentication()->register(
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['email'] ?? ''),
                    (string) ($_POST['phone'] ?? ''),
                    (string) ($_POST['password'] ?? ''),
                    (string) ($_POST['password_confirmation'] ?? ''),
                );
                session_regenerate_id(true);
                $_SESSION['user'] = $user->toSession();
                $this->respondSuccess('Tu cuenta está pronta. Ya podés reservar a tu nombre.', '/#reservas');
            }

            if ($action === 'login') {
                $user = $this->authentication()->authenticate(
                    (string) ($_POST['email'] ?? ''),
                    (string) ($_POST['password'] ?? ''),
                );
                session_regenerate_id(true);
                $_SESSION['user'] = $user->toSession();
                $this->respondSuccess('Bienvenido/a de nuevo.', '/#reservas');
            }

            $this->respondError('La acción solicitada no es válida.', 400, '/cuenta.php');
        } catch (DomainException $error) {
            $this->respondError($error->getMessage(), 422, $action === 'register' ? '/cuenta.php#registrarse' : '/cuenta.php#iniciar-sesion');
        } catch (Throwable $error) {
            error_log($error->__toString());
            $this->respondError('No pudimos completar el trámite. Revisá la conexión e intentá nuevamente.', 503, '/cuenta.php');
        }
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function authentication(): AuthenticationService
    {
        return $this->authentication ?? throw new \LogicException('El servicio de autenticación no está disponible.');
    }

    private function respondSuccess(string $message, string $redirect): never
    {
        if ($this->wantsJson()) {
            \json_response(['message' => $message, 'redirect' => $redirect]);
        }
        \flash('success', $message);
        header('Location: ' . $redirect);
        exit;
    }

    private function respondError(string $message, int $status, string $redirect): never
    {
        if ($this->wantsJson()) {
            \json_response(['error' => $message], $status);
        }
        \flash('error', $message);
        header('Location: ' . $redirect);
        exit;
    }
}
