<?php
// ============================================================
// BACKEND - API REST para gestión de productos del minimarket
// Archivo: index.php
// ============================================================

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'db_minimarket_pro413');
define('DB_USER', 'minimarket_user');
define('DB_PASS', 'minimarket1234');

// Permitir solicitudes desde cualquier origen (CORS)
// Esto permite que el frontend se comunique con el backend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Si es una solicitud OPTIONS (preflight), responder con OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// FUNCIÓN: Conectar a la base de datos
// ============================================================
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conexion->connect_error) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error de conexión a la base de datos: ' . $conexion->connect_error
        ]);
        exit();
    }
    
    $conexion->set_charset('utf8');
    return $conexion;
}

// ============================================================
// FUNCIÓN: Enviar respuesta JSON
// ============================================================
function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

// ============================================================
// OBTENER la ruta y el método HTTP
// ============================================================
$metodo = $_SERVER['REQUEST_METHOD'];
$ruta   = isset($_GET['ruta']) ? $_GET['ruta'] : '';
$id     = isset($_GET['id'])   ? (int)$_GET['id'] : null;

// ============================================================
// RUTAS DE LA API
// ============================================================

// GET /api/?ruta=productos → Listar todos los productos activos
// GET /api/?ruta=productos&id=5 → Obtener un producto por ID
if ($metodo === 'GET' && $ruta === 'productos') {
    $db = conectarDB();
    
    if ($id) {
        // Obtener un producto específico
        $stmt = $db->prepare("SELECT * FROM Producto WHERE id = ? AND eliminado = 0");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto  = $resultado->fetch_assoc();
        
        if ($producto) {
            responder($producto);
        } else {
            responder(['error' => 'Producto no encontrado'], 404);
        }
    } else {
        // Obtener todos los productos activos (eliminado = 0)
        $resultado = $db->query("SELECT * FROM Producto WHERE eliminado = 0 ORDER BY id ASC");
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        responder($productos);
    }
}

// POST /api/?ruta=productos → Crear un nuevo producto
elseif ($metodo === 'POST' && $ruta === 'productos') {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos requeridos
    if (empty($datos['nombre']) || empty($datos['proveedor']) || 
        empty($datos['fecha_vencimiento']) || !isset($datos['precio']) || 
        !isset($datos['stock'])) {
        responder(['error' => 'Todos los campos son obligatorios'], 400);
    }
    
    // Validar que precio y stock no sean negativos
    if ($datos['precio'] < 0) {
        responder(['error' => 'El precio no puede ser negativo'], 400);
    }
    if ($datos['stock'] < 0) {
        responder(['error' => 'El stock no puede ser negativo'], 400);
    }
    
    $db = conectarDB();
    
    $stmt = $db->prepare(
        "INSERT INTO Producto (nombre, proveedor, fecha_vencimiento, precio, stock) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'sssdi',
        $datos['nombre'],
        $datos['proveedor'],
        $datos['fecha_vencimiento'],
        $datos['precio'],
        $datos['stock']
    );
    
    if ($stmt->execute()) {
        responder([
            'mensaje'    => 'Producto creado exitosamente',
            'id_creado'  => $db->insert_id
        ], 201);
    } else {
        responder(['error' => 'Error al crear el producto'], 500);
    }
}

// PUT /api/?ruta=productos&id=5 → Editar un producto existente
elseif ($metodo === 'PUT' && $ruta === 'productos' && $id) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos requeridos
    if (empty($datos['nombre']) || empty($datos['proveedor']) || 
        empty($datos['fecha_vencimiento']) || !isset($datos['precio']) || 
        !isset($datos['stock'])) {
        responder(['error' => 'Todos los campos son obligatorios'], 400);
    }
    
    // Validar que precio y stock no sean negativos
    if ($datos['precio'] < 0) {
        responder(['error' => 'El precio no puede ser negativo'], 400);
    }
    if ($datos['stock'] < 0) {
        responder(['error' => 'El stock no puede ser negativo'], 400);
    }
    
    $db = conectarDB();
    
    $stmt = $db->prepare(
        "UPDATE Producto SET nombre=?, proveedor=?, fecha_vencimiento=?, precio=?, stock=? 
         WHERE id=? AND eliminado=0"
    );
    $stmt->bind_param(
        'sssdii',
        $datos['nombre'],
        $datos['proveedor'],
        $datos['fecha_vencimiento'],
        $datos['precio'],
        $datos['stock'],
        $id
    );
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        responder(['mensaje' => 'Producto actualizado exitosamente']);
    } else {
        responder(['error' => 'Producto no encontrado o sin cambios'], 404);
    }
}

// DELETE /api/?ruta=productos&id=5 → Eliminación LÓGICA (cambia eliminado a 1)
elseif ($metodo === 'DELETE' && $ruta === 'productos' && $id) {
    $db = conectarDB();
    
    // Solo cambiamos el campo "eliminado" a 1, NO borramos el registro
    $stmt = $db->prepare("UPDATE Producto SET eliminado = 1 WHERE id = ? AND eliminado = 0");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        responder(['mensaje' => 'Producto eliminado lógicamente (el registro se conserva en la base de datos)']);
    } else {
        responder(['error' => 'Producto no encontrado o ya eliminado'], 404);
    }
}

// Ruta no encontrada
else {
    responder([
        'error'  => 'Ruta no encontrada',
        'rutas_disponibles' => [
            'GET    /api/?ruta=productos'        => 'Listar todos los productos activos',
            'GET    /api/?ruta=productos&id=N'   => 'Ver un producto por ID',
            'POST   /api/?ruta=productos'        => 'Crear un nuevo producto',
            'PUT    /api/?ruta=productos&id=N'   => 'Editar un producto',
            'DELETE /api/?ruta=productos&id=N'   => 'Eliminar lógicamente un producto',
        ]
    ], 404);
}
