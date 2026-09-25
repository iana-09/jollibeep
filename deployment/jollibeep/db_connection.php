<?php
/**
 * JolliBeep database connection.
 *
 * Default: local XAMPP MySQL.
 * Supabase: copy supabase_config.example.php to supabase_config.php and set driver to supabase.
 */

class PgCompatResult
{
    public int $num_rows = 0;
    private array $rows;
    private int $index = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc()
    {
        if (!isset($this->rows[$this->index])) {
            return null;
        }

        return $this->rows[$this->index++];
    }
}

class PgCompatStatement
{
    public int $num_rows = 0;
    public string $error = '';
    private PDO $pdo;
    private string $sql;
    private array $params = [];
    private ?PgCompatResult $result = null;
    private array $boundResultRefs = [];

    public function __construct(PDO $pdo, string $sql)
    {
        $this->pdo = $pdo;
        $this->sql = PgCompatConnection::translateSql($sql);
    }

    public function bind_param(string $types, &...$vars): bool
    {
        $this->params = &$vars;
        return true;
    }

    public function execute(): bool
    {
        try {
            $stmt = $this->pdo->prepare($this->sql);
            $values = [];
            foreach ($this->params as $value) {
                $values[] = $value === '' ? null : $value;
            }
            $stmt->execute($values);

            if (stripos(ltrim($this->sql), 'select') === 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->result = new PgCompatResult($rows);
                $this->num_rows = count($rows);
            } else {
                $this->result = new PgCompatResult([]);
                $this->num_rows = $stmt->rowCount();
            }

            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function store_result(): bool
    {
        if (!$this->result) {
            $this->execute();
        }

        return true;
    }

    public function get_result(): PgCompatResult
    {
        return $this->result ?: new PgCompatResult([]);
    }

    public function bind_result(&...$vars): bool
    {
        $this->boundResultRefs = &$vars;
        return true;
    }

    public function fetch(): bool
    {
        $row = $this->get_result()->fetch_assoc();
        if (!$row) {
            return false;
        }

        $values = array_values($row);
        foreach ($this->boundResultRefs as $index => &$ref) {
            $ref = $values[$index] ?? null;
        }

        return true;
    }

    public function close(): void
    {
    }
}

class PgCompatConnection
{
    public string $connect_error = '';
    public string $error = '';
    private ?PDO $pdo = null;

    public function __construct(array $config)
    {
        if (!extension_loaded('pdo_pgsql')) {
            $this->connect_error = 'pdo_pgsql is not enabled. Enable extension=pdo_pgsql in C:\\xampp\\php\\php.ini, then restart Apache.';
            return;
        }

        try {
            $host = $config['host'] ?? '';
            $port = (int)($config['port'] ?? 6543);
            $database = $config['database'] ?? 'postgres';
            $user = $config['user'] ?? '';
            $password = $config['password'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode=require";
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
        }
    }

    public static function translateSql(string $sql): string
    {
        $trimmed = trim($sql);

        if (stripos($trimmed, "SHOW COLUMNS FROM users LIKE 'is_admin'") === 0) {
            return "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'is_admin'";
        }

        $sql = str_replace('TINYINT(1)', 'integer', $sql);
        $sql = preg_replace('/ADD COLUMN is_admin integer NOT NULL DEFAULT 0/i', 'ADD COLUMN IF NOT EXISTS is_admin integer NOT NULL DEFAULT 0', $sql);

        return $sql;
    }

    public function prepare(string $sql): PgCompatStatement
    {
        return new PgCompatStatement($this->pdo, $sql);
    }

    public function query(string $sql)
    {
        try {
            $translated = self::translateSql($sql);
            $stmt = $this->pdo->query($translated);

            if (stripos(ltrim($translated), 'select') === 0) {
                return new PgCompatResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            }

            return new PgCompatResult([]);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}

$supabaseConfigPath = __DIR__ . DIRECTORY_SEPARATOR . 'supabase_config.php';
$config = is_file($supabaseConfigPath) ? require $supabaseConfigPath : [];
$driver = $config['driver'] ?? getenv('JOLLIBEEP_DB_DRIVER') ?: 'mysql';

if ($driver === 'supabase') {
    $config = array_merge([
        'host' => getenv('SUPABASE_DB_HOST') ?: '',
        'port' => getenv('SUPABASE_DB_PORT') ?: 6543,
        'database' => getenv('SUPABASE_DB_NAME') ?: 'postgres',
        'user' => getenv('SUPABASE_DB_USER') ?: '',
        'password' => getenv('SUPABASE_DB_PASSWORD') ?: '',
    ], $config);

    $conn = new PgCompatConnection($config);
} else {
    $host = 'localhost';
    $db = 'jollibee';
    $user = 'root';
    $pass = '';
    $port = 3307;

    $conn = new mysqli($host, $user, $pass, $db, $port);
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
