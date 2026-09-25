<?php
final class SupabaseVercelSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private ?string $lockedSessionId = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        if ($this->lockedSessionId !== null) {
            $stmt = $this->pdo->prepare('SELECT pg_advisory_unlock(hashtext(:session_id)::bigint)');
            $stmt->execute(['session_id' => $this->lockedSessionId]);
            $this->lockedSessionId = null;
        }
        return true;
    }

    public function read(string $id): string|false
    {
        $lock = $this->pdo->prepare('SELECT pg_advisory_lock(hashtext(:session_id)::bigint)');
        $lock->execute(['session_id' => $id]);
        $this->lockedSessionId = $id;

        $stmt = $this->pdo->prepare('SELECT payload FROM public.app_sessions WHERE session_id = :session_id AND updated_at > to_timestamp(:cutoff)');
        $stmt->execute([
            'session_id' => $id,
            'cutoff' => time() - max(1, (int)ini_get('session.gc_maxlifetime')),
        ]);
        $payload = $stmt->fetchColumn();
        return $payload === false ? '' : (string)$payload;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO public.app_sessions (session_id, payload, updated_at) VALUES (:session_id, :payload, now()) ON CONFLICT (session_id) DO UPDATE SET payload = EXCLUDED.payload, updated_at = now()');
        return $stmt->execute(['session_id' => $id, 'payload' => $data]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM public.app_sessions WHERE session_id = :session_id');
        return $stmt->execute(['session_id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM public.app_sessions WHERE updated_at < to_timestamp(:cutoff)');
        $stmt->execute(['cutoff' => time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db_connection.php';
if (!empty($conn->connect_error)) {
    error_log('Supabase session storage connection failed.');
    http_response_code(503);
    exit('Session storage unavailable.');
}

session_set_save_handler(new SupabaseVercelSessionHandler($conn->getPdo()), true);
$isHttps = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);