<?php
return $pdo->lastInsertId();



function edit_purchase(PDO $pdo, $purchase_id, $data, $editor_id) {
$fields = [];
$params = [':id'=>$purchase_id];
foreach (['product','quantity','note','user_id'] as $f) {
if (isset($data[$f])) { $fields[] = "$f = :$f"; $params[":$f"] = $data[$f]; }
}
if (empty($fields)) return false;
$sql = "UPDATE purchases SET " . implode(', ', $fields) . ", last_edited_by = :led, last_edited_at = NOW() WHERE id = :id";
$params[':led'] = $editor_id;
$stmt = $pdo->prepare($sql);
return $stmt->execute($params);
}


function query_purchases(PDO $pdo, $filters = []) {
$sql = "SELECT p.*, u.name as buyer_name, c.name as created_by_name, le.name as last_edited_by_name FROM purchases p JOIN users u ON p.user_id=u.id LEFT JOIN users c ON p.created_by = c.id LEFT JOIN users le ON p.last_edited_by = le.id WHERE 1=1";
$params = [];
if (!empty($filters['user_id'])) { $sql .= " AND p.user_id = :uid"; $params[':uid'] = $filters['user_id']; }
if (!empty($filters['product'])) { $sql .= " AND p.product = :prod"; $params[':prod'] = $filters['product']; }
if (!empty($filters['from'])) { $sql .= " AND p.created_at >= :from"; $params[':from'] = $filters['from']; }
if (!empty($filters['to'])) { $sql .= " AND p.created_at <= :to"; $params[':to'] = $filters['to']; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
return $stmt->fetchAll();
}


function purchases_count_by_user(PDO $pdo) {
$stmt = $pdo->query("SELECT u.id, u.name, COUNT(p.id) as total FROM users u LEFT JOIN purchases p ON p.user_id = u.id GROUP BY u.id ORDER BY total DESC");
return $stmt->fetchAll();
}


// User utilities
function get_all_users(PDO $pdo) {
return $pdo->query("SELECT id,name,email,role FROM users ORDER BY name")->fetchAll();
}


function create_user(PDO $pdo, $name, $email, $password, $role='user') {
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (:n,:e,:p,:r)");
$stmt->execute([':n'=>$name,':e'=>$email,':p'=>$hash,':r'=>$role]);
$id = $pdo->lastInsertId();
ensure_user_in_queue($pdo, $id);
return $id;
}

/**
 * Ensure the user has an entry in the queue table; create one if missing.
 * Adjust table/column names if your schema differs.
 */
function ensure_user_in_queue(PDO $pdo, $user_id) {
	$stmt = $pdo->prepare("SELECT id FROM queue WHERE user_id = :uid LIMIT 1");
	$stmt->execute([':uid' => $user_id]);
	if (!$stmt->fetch()) {
		$ins = $pdo->prepare("INSERT INTO queue (user_id, created_at) VALUES (:uid, NOW())");
		$ins->execute([':uid' => $user_id]);
	}
}


function get_time_since_last_purchase(PDO $pdo) {
$stmt = $pdo->query("SELECT created_at FROM purchases ORDER BY created_at DESC LIMIT 1");
$row = $stmt->fetch();
if (!$row) return null;
$last = new DateTime($row['created_at']);
$now = new DateTime();
return $now->diff($last);
}