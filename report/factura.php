<?php
session_start();
require_once '../library/configServer.php';
require_once '../library/consulSQL.php';

// Configuración de la conexión a la base de datos
$conn = mysqli_connect(SERVER, USER, PASS, BD);
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
echo "Conexión exitosa a la base de datos<br>"; // Línea de debug

$id = isset($_GET['id']) ? $_GET['id'] : '';
$tipo_impresion = isset($_GET['tipo']) ? $_GET['tipo'] : 'pdf';

echo "ID del pedido: " . $id . "<br>"; // Línea de debug

// Verificar el número total de registros en la tabla venta
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM venta");
$data = mysqli_fetch_assoc($result);
echo "Total de registros en la tabla venta: " . $data['total'] . "<br>"; // Línea de debug

if ($tipo_impresion == 'html') {
    generarFacturaHTML($id, $conn);
} else {
    generarFacturaPDF($id, $conn);
}

function generarFacturaHTML($id, $conn) {
    $query = "SELECT * FROM venta WHERE NumPedido = ?";
    echo "Consulta SQL: " . $query . "<br>"; // Línea de debug
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dVenta = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    if (!$dVenta) {
        die("No se encontró el pedido especificado.");
    }
    
    $query = "SELECT * FROM cliente WHERE NIT = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $dVenta['NIT']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dCliente = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    if (!$dCliente) {
        die("No se encontró información del cliente.");
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Factura del Pedido #<?php echo htmlspecialchars($id); ?></title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 20px; }
            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>TECNOMUNDO</h1>
            <h2>Factura del Pedido #<?php echo htmlspecialchars($id); ?></h2>
        </div>
        <table>
            <tr><th>Fecha del pedido</th><td><?php echo htmlspecialchars($dVenta['Fecha']); ?></td></tr>
            <tr><th>Nombre del cliente</th><td><?php echo htmlspecialchars($dCliente['NombreCompleto']." ".$dCliente['Apellido']); ?></td></tr>
            <tr><th>DNI/CÉDULA</th><td><?php echo htmlspecialchars($dCliente['NIT']); ?></td></tr>
            <tr><th>Dirección</th><td><?php echo htmlspecialchars($dCliente['Direccion']); ?></td></tr>
            <tr><th>Teléfono</th><td><?php echo htmlspecialchars($dCliente['Telefono']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($dCliente['Email']); ?></td></tr>
        </table>

        <h3>Detalles del Pedido</h3>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
            <?php
            $suma = 0;
            $query = "SELECT * FROM detalle WHERE NumPedido = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $id);
            mysqli_stmt_execute($stmt);
            $sDet = mysqli_stmt_get_result($stmt);
            
            while($fila1 = mysqli_fetch_array($sDet, MYSQLI_ASSOC)){
                $query = "SELECT * FROM producto WHERE CodigoProd = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $fila1['CodigoProd']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $fila = mysqli_fetch_array($result, MYSQLI_ASSOC);
                
                $subtotal = $fila1['PrecioProd'] * $fila1['CantidadProductos'];
                $suma += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila['NombreProd']); ?></td>
                    <td>$<?php echo htmlspecialchars($fila1['PrecioProd']); ?></td>
                    <td><?php echo htmlspecialchars($fila1['CantidadProductos']); ?></td>
                    <td>$<?php echo htmlspecialchars($subtotal); ?></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <th colspan="3">Total</th>
                <td>$<?php echo number_format($suma, 2); ?></td>
            </tr>
        </table>

        <div class="no-print">
            <button onclick="window.print()">Imprimir Factura</button>
        </div>
    </body>
    </html>
    <?php
}

function generarFacturaPDF($id, $conn) {
    require './fpdf/fpdf.php';
    
    $query = "SELECT * FROM venta WHERE NumPedido = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dVenta = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    if (!$dVenta) {
        die("No se encontró el pedido especificado.");
    }
    
    $query = "SELECT * FROM cliente WHERE NIT = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $dVenta['NIT']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dCliente = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    if (!$dCliente) {
        die("No se encontró información del cliente.");
    }
    
    $pdf = new FPDF('P','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'CELL LUXE',0,1,'C');
    $pdf->Cell(0,10,'Factura del Pedido #'.$id,0,1,'C');
    
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Fecha del pedido: '.$dVenta['Fecha'],0,1);
    $pdf->Cell(0,10,'Nombre del cliente: '.$dCliente['NombreCompleto']." ".$dCliente['Apellido'],0,1);
    $pdf->Cell(0,10,'DNI/CÉDULA: '.$dCliente['NIT'],0,1);
    $pdf->Cell(0,10,'Dirección: '.$dCliente['Direccion'],0,1);
    $pdf->Cell(0,10,'Teléfono: '.$dCliente['Telefono'],0,1);
    $pdf->Cell(0,10,'Email: '.$dCliente['Email'],0,1);
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(60,10,'Producto',1);
    $pdf->Cell(30,10,'Precio',1);
    $pdf->Cell(30,10,'Cantidad',1);
    $pdf->Cell(30,10,'Subtotal',1);
    $pdf->Ln();
    
    $suma = 0;
    $query = "SELECT * FROM detalle WHERE NumPedido = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $sDet = mysqli_stmt_get_result($stmt);
    
    $pdf->SetFont('Arial','',12);
    while($fila1 = mysqli_fetch_array($sDet, MYSQLI_ASSOC)){
        $query = "SELECT * FROM producto WHERE CodigoProd = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $fila1['CodigoProd']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $fila = mysqli_fetch_array($result, MYSQLI_ASSOC);
        
        $subtotal = $fila1['PrecioProd'] * $fila1['CantidadProductos'];
        $suma += $subtotal;
        
        $pdf->Cell(60,10,$fila['NombreProd'],1);
        $pdf->Cell(30,10,'$'.$fila1['PrecioProd'],1);
        $pdf->Cell(30,10,$fila1['CantidadProductos'],1);
        $pdf->Cell(30,10,'$'.$subtotal,1);
        $pdf->Ln();
    }
    
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(120,10,'Total',1);
    $pdf->Cell(30,10,'$'.number_format($suma, 2),1);
    
    $pdf->Output('Factura-#'.$id,'I');
}

mysqli_close($conn);
?>