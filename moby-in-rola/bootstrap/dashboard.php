<?php
require_once __DIR__ . '/../functions.php';

if (!function_exists('require_login')) {
    /**
     * Fallback require_login implementation: start the session if needed and
     * redirect to the login page when no user is authenticated.
     */
    function require_login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        return true;
    }
}

require_login();

if (!function_exists('current_user')) {
    /**
     * Fallback current_user implementation: ensure the session is started,
     * return null if no authenticated user, or fetch the user row from the DB.
     */
    function current_user() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        // Use the global PDO instance if available
        global $pdo;
        if (!isset($pdo) || !$pdo) {
            // If PDO is not available, return a minimal user array
            return [
                'id' => $_SESSION['user_id'],
                'name' => 'Unknown',
                'role' => 'user',
            ];
        }

        try {
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

$me = current_user();

// Dados principais
if (!function_exists('get_next_in_queue')) {
    /**
     * Fallback implementation to fetch the next item in the queue.
     * This will only be defined if there isn't an implementation provided elsewhere.
     */
    function get_next_in_queue($pdo) {
        try {
            $stmt = $pdo->query("SELECT q.*, u.name AS name FROM queue q JOIN users u ON q.user_id = u.id ORDER BY q.position ASC LIMIT 1");
            return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

$next = get_next_in_queue($pdo);

$last = $pdo->query("
    SELECT p.*, u.name AS buyer_name
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$since_last = $last ? get_time_since_last_purchase($pdo) : null;
$recent = query_purchases($pdo, ['from' => date('Y-m-d H:i:s', strtotime('-7 days'))]);
$counts = purchases_count_by_user($pdo);
$users = get_all_users($pdo);
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<main class="container">
    <h1>Dashboard</h1>
    <p>Usuário: <?= htmlspecialchars($me['name'], ENT_QUOTES, 'UTF-8') ?> |
        <a href="logout.php">Sair</a>
    </p>

    <section>
        <h2>Próximo da fila</h2>
        <?php if ($next): ?>
            <p><?= htmlspecialchars($next['name'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p>Fila vazia.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Última compra</h2>
        <?php if ($last): ?>
            <p>
                <?= htmlspecialchars($last['created_at'], ENT_QUOTES, 'UTF-8') ?> —
                <?= htmlspecialchars($last['buyer_name'], ENT_QUOTES, 'UTF-8') ?> —
                <?= htmlspecialchars($last['product'], ENT_QUOTES, 'UTF-8') ?>
            </p>
            <?php if ($since_last): ?>
                <p>Tempo desde a última:
                    <?= $since_last->days ?>d <?= $since_last->h ?>h <?= $since_last->i ?>m
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p>Sem compras ainda.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Resumo últimas compras (7 dias)</h2>
        <ul>
            <?php foreach (array_slice($recent, 0, 10) as $r): ?>
                <li>
                    <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?> -
                    <?= htmlspecialchars($r['buyer_name'], ENT_QUOTES, 'UTF-8') ?> -
                    <?= htmlspecialchars($r['product'], ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section>
        <h2>Compras por usuário</h2>
        <ul>
            <?php foreach ($counts as $c): ?>
                <li>
                    <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>:
                    <?= htmlspecialchars($c['total'], ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <p>
        <a href="purchases.php">Ver Compras</a> |
        <a href="queue.php">Ver Fila</a> |
        <?php if ($me['role'] === 'admin'): ?>
            <a href="users.php">Usuários</a>
        <?php endif; ?>
    </p>

    <hr>
    <h3>Registrar compra (sua vez)</h3>
    <form method="POST" action="purchases_action.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

        <label>Usuário (se admin pode escolher)<br>
            <select name="user_id">
                <?php foreach ($users as $u): ?>
                    <option value="<?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Produto<br>
            <select name="product">
                <option value="cafe">Café</option>
                <option value="filtro">Filtro</option>
                <option value="cafe_e_filtro">Café + Filtro</option>
            </select>
        </label><br>

        <label>Quantidade<br>
            <input type="number" name="quantity" value="1" min="1">
        </label><br>

        <label>Nota<br>
            <input name="note">
        </label><br>

        <button type="submit">Registrar Compra</button>
    </form>
</main>
</body>
</html>
