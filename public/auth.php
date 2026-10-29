<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cuenta.php');
    exit;
}
require_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'logout') {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('success', 'Cerraste sesión correctamente.');
    header('Location: /');
    exit;
}

try {
    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $nameLength = iconv_strlen($name, 'UTF-8');
        if ($nameLength === false || $nameLength < 2 || $nameLength > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Revisá tu nombre y correo electrónico.');
        }
        if (strlen($password) < 8 || $password !== $confirmation) {
            throw new DomainException('La contraseña debe tener 8 caracteres o más y ambas deben coincidir.');
        }
        $insert = db()->prepare('INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        try {
            $insert->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new DomainException('Ese correo ya tiene una cuenta. Iniciá sesión.');
            }
            throw $error;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email];
        flash('success', 'Tu cuenta está pronta. Ya podés reservar a tu nombre.');
        header('Location: /#reservas');
        exit;
    }

    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $query = db()->prepare('SELECT id, full_name, email, password_hash FROM users WHERE email = ?');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new DomainException('El correo o la contraseña no coinciden.');
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['full_name'], 'email' => $user['email']];
        flash('success', 'Bienvenido/a de nuevo.');
        header('Location: /#reservas');
        exit;
    }
    throw new DomainException('La acción solicitada no es válida.');
} catch (DomainException $error) {
    flash('error', $error->getMessage());
} catch (Throwable $error) {
    error_log($error->__toString());
    flash('error', 'No pudimos completar el trámite. Revisá la conexión e intentá nuevamente.');
}

header('Location: /cuenta.php' . ($action === 'register' ? '#registrarse' : '#iniciar-sesion'));
exit;
