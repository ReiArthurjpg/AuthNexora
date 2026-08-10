<?php
declare(strict_types=1);

/**
 * ============================================================
 * AuthNexora — Seed: Usuário Administrador
 * ============================================================
 *
 * Como usar:
 *   docker exec -it <nome_do_container> php /var/www/seed-admin.php
 *
 * No Render (via SSH ou Shell):
 *   php /var/www/seed-admin.php
 * ============================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$env = require __DIR__ . '/src/Config/env.php';
$db  = $env['db'];

// ── Credenciais do admin ────────────────────────────────────
$name     = 'Administrador Nexora';
$email    = 'admin@nexora.com';
$password = 'Admin@123';
$academy  = 'Nexora Headquarter';
$phone    = '+55 11 99999-9999';
$belt     = 'Preta';
$degree   = '4º Grau';

// ── Conexão com o banco ─────────────────────────────────────
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'], $db['port'], $db['database'], $db['charset']
    );
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Conectado ao banco: {$db['database']}\n";
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Gerar hash Argon2id correto ─────────────────────────────
$passwordHash = password_hash($password, PASSWORD_ARGON2ID);
echo "🔐 Hash gerado: {$passwordHash}\n";

// ── Verificar se já existe ──────────────────────────────────
$check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute(['email' => strtolower($email)]);
$existing = $check->fetch();

if ($existing) {
    // Atualizar senha e desbloquear conta
    $update = $pdo->prepare('
        UPDATE users
        SET password_hash = :hash,
            failed_login_attempts = 0,
            is_email_verified = 1
        WHERE email = :email
    ');
    $update->execute(['hash' => $passwordHash, 'email' => strtolower($email)]);
    echo "🔄 Usuário admin atualizado! ID: {$existing['id']}\n";
} else {
    // Inserir novo admin
    $insert = $pdo->prepare('
        INSERT INTO users (name, email, password_hash, academy_name, phone, belt, degree, is_email_verified, is_two_factor_enabled, failed_login_attempts)
        VALUES (:name, :email, :password_hash, :academy_name, :phone, :belt, :degree, 1, 0, 0)
    ');
    $insert->execute([
        'name'          => $name,
        'email'         => strtolower($email),
        'password_hash' => $passwordHash,
        'academy_name'  => $academy,
        'phone'         => $phone,
        'belt'          => $belt,
        'degree'        => $degree,
    ]);
    $id = $pdo->lastInsertId();
    echo "✅ Usuário admin criado! ID: {$id}\n";
}

echo "\n📋 Credenciais de acesso:\n";
echo "   E-mail : {$email}\n";
echo "   Senha  : {$password}\n";
echo "\n🎉 Pronto! Agora faça login em: POST /auth/login\n";
