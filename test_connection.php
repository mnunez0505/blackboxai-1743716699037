<?php
require_once 'config/config.php';

try {
    echo "Intentando conectar a Oracle...\n";
    
    $conn = getOracleConnection();
    
    if ($conn) {
        echo "¡Conexión exitosa!\n\n";
        
        // Probar una consulta simple
        echo "Probando consulta...\n";
        $sql = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = oci_parse($conn, $sql);
        
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            echo "Usuarios encontrados: " . $row['TOTAL'] . "\n";
        }
        
        // Probar las secuencias
        echo "\nVerificando secuencias...\n";
        $sequences = [
            'usuarios_seq',
            'clientes_seq',
            'facturas_seq',
            'cheques_seq',
            'historial_cheques_seq',
            'historial_facturas_seq',
            'historial_clientes_seq',
            'log_accesos_seq'
        ];
        
        foreach ($sequences as $seq) {
            $sql = "SELECT $seq.NEXTVAL FROM DUAL";
            $stmt = oci_parse($conn, $sql);
            if (oci_execute($stmt)) {
                echo "✓ Secuencia $seq funciona correctamente\n";
            } else {
                echo "✗ Error en secuencia $seq\n";
            }
        }
        
        // Verificar tablas principales
        echo "\nVerificando tablas principales...\n";
        $tables = [
            'usuarios',
            'clientes',
            'facturas',
            'cheques',
            'historial_cheques',
            'historial_facturas',
            'historial_clientes',
            'log_accesos'
        ];
        
        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as total FROM $table";
            $stmt = oci_parse($conn, $sql);
            if (oci_execute($stmt)) {
                $row = oci_fetch_assoc($stmt);
                echo "✓ Tabla $table existe y contiene " . $row['TOTAL'] . " registros\n";
            } else {
                echo "✗ Error al acceder a la tabla $table\n";
            }
        }
        
        // Verificar vistas
        echo "\nVerificando vistas...\n";
        $views = [
            'v_cheques_pendientes',
            'v_facturas_sin_cheque'
        ];
        
        foreach ($views as $view) {
            $sql = "SELECT COUNT(*) as total FROM $view";
            $stmt = oci_parse($conn, $sql);
            if (oci_execute($stmt)) {
                echo "✓ Vista $view funciona correctamente\n";
            } else {
                echo "✗ Error al acceder a la vista $view\n";
            }
        }
        
        // Cerrar conexión
        closeOracleConnection($conn);
        echo "\nConexión cerrada correctamente\n";
        
    } else {
        echo "Error: No se pudo establecer la conexión\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}