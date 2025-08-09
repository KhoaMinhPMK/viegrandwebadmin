<?php
// Simple installer to run schema_tasks_rbac.sql against admin DB
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection failed\n";
    exit;
}

$sqlFile = __DIR__ . '/schema_tasks_rbac.sql';
if (!file_exists($sqlFile)) {
    http_response_code(404);
    echo "Schema file not found\n";
    exit;
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    http_response_code(400);
    echo "Schema file empty\n";
    exit;
}

// naive split by semicolon at line end
$statements = preg_split('/;\s*\n/', $sql);
$executed = 0;
$pdo->beginTransaction();
try {
    foreach ($statements as $statement) {
        $stm = trim($statement);
        if ($stm === '' || strpos($stm, '--') === 0) continue;
        $pdo->exec($stm);
        $executed++;
    }
    $pdo->commit();
    echo "OK: executed {$executed} statements\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error executing schema: " . $e->getMessage() . "\n";
}

?>


