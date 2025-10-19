<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$search = $_GET['q'] ?? '';
$page = $_GET['page'] ?? 1;
$per_page = 10;

// Construir consulta de búsqueda
$query = "SELECT id, nombre, apellido, email, identificacion, telefono 
          FROM usuarios 
          WHERE tipo_usuario = 'paciente' 
          AND activo = 1";

$params = [];

if (!empty($search)) {
    $query .= " AND (nombre LIKE :search 
                     OR apellido LIKE :search 
                     OR identificacion LIKE :search 
                     OR email LIKE :search)";
    $searchParam = '%' . $search . '%';
    $params[':search'] = $searchParam;
}

$query .= " ORDER BY nombre, apellido LIMIT :offset, :limit";

$stmt = $db->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$offset = ($page - 1) * $per_page;
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);

$stmt->execute();

$patients = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $patients[] = [
        'id' => $row['id'],
        'text' => $row['nombre'] . ' ' . $row['apellido'] . ' - ' . ($row['identificacion'] ?: $row['email']),
        'nombre' => $row['nombre'],
        'apellido' => $row['apellido'],
        'email' => $row['email'],
        'identificacion' => $row['identificacion'],
        'telefono' => $row['telefono']
    ];
}

// Contar total para paginación
$countQuery = "SELECT COUNT(*) as total 
               FROM usuarios 
               WHERE tipo_usuario = 'paciente' 
               AND activo = 1";

if (!empty($search)) {
    $countQuery .= " AND (nombre LIKE :search 
                         OR apellido LIKE :search 
                         OR identificacion LIKE :search 
                         OR email LIKE :search)";
}

$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    if ($key === ':search') {
        $countStmt->bindValue($key, $value);
    }
}
$countStmt->execute();
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

$result = [
    'results' => $patients,
    'pagination' => [
        'more' => ($page * $per_page) < $total
    ]
];

header('Content-Type: application/json');
echo json_encode($result);
?>
