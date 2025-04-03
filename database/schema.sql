-- Crear secuencias para los IDs
CREATE SEQUENCE usuarios_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE clientes_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE facturas_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE cheques_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE historial_cheques_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE historial_facturas_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE historial_clientes_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE log_accesos_seq START WITH 1 INCREMENT BY 1;

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id NUMBER DEFAULT usuarios_seq.NEXTVAL PRIMARY KEY,
    username VARCHAR2(50) NOT NULL UNIQUE,
    password_hash VARCHAR2(255) NOT NULL,
    nombre VARCHAR2(100) NOT NULL,
    email VARCHAR2(100),
    estado CHAR(1) DEFAULT 'A' CHECK (estado IN ('A', 'I')),
    fecha_registro TIMESTAMP DEFAULT SYSTIMESTAMP,
    fecha_modificacion TIMESTAMP,
    usuario_registro NUMBER,
    usuario_modificacion NUMBER
);

-- Tabla de Clientes
CREATE TABLE clientes (
    id NUMBER DEFAULT clientes_seq.NEXTVAL PRIMARY KEY,
    nombre VARCHAR2(100) NOT NULL,
    identificacion VARCHAR2(13) NOT NULL UNIQUE,
    direccion VARCHAR2(200),
    telefono VARCHAR2(15),
    email VARCHAR2(100),
    estado CHAR(1) DEFAULT 'A' CHECK (estado IN ('A', 'I')),
    observaciones VARCHAR2(500),
    fecha_registro TIMESTAMP DEFAULT SYSTIMESTAMP,
    fecha_modificacion TIMESTAMP,
    usuario_registro NUMBER REFERENCES usuarios(id),
    usuario_modificacion NUMBER REFERENCES usuarios(id)
);

-- Tabla de Facturas
CREATE TABLE facturas (
    id NUMBER DEFAULT facturas_seq.NEXTVAL PRIMARY KEY,
    numero VARCHAR2(20) NOT NULL UNIQUE,
    cliente_id NUMBER REFERENCES clientes(id),
    fecha DATE NOT NULL,
    monto NUMBER(12,2) NOT NULL,
    concepto VARCHAR2(500),
    estado CHAR(1) DEFAULT 'A' CHECK (estado IN ('A', 'C', 'N')), -- A: Activa, C: Cruzada, N: Anulada
    fecha_registro TIMESTAMP DEFAULT SYSTIMESTAMP,
    fecha_modificacion TIMESTAMP,
    usuario_registro NUMBER REFERENCES usuarios(id),
    usuario_modificacion NUMBER REFERENCES usuarios(id)
);

-- Tabla de Cheques
CREATE TABLE cheques (
    id NUMBER DEFAULT cheques_seq.NEXTVAL PRIMARY KEY,
    numero_cheque VARCHAR2(20) NOT NULL UNIQUE,
    beneficiario VARCHAR2(100) NOT NULL,
    monto NUMBER(12,2) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_deposito DATE,
    cliente_id NUMBER REFERENCES clientes(id),
    factura_id NUMBER REFERENCES facturas(id),
    estado VARCHAR2(10) DEFAULT 'INGRESADO' 
        CHECK (estado IN ('INGRESADO', 'CRUZADO', 'DEPOSITADO', 'ANULADO')),
    fecha_registro TIMESTAMP DEFAULT SYSTIMESTAMP,
    fecha_modificacion TIMESTAMP,
    usuario_registro NUMBER REFERENCES usuarios(id),
    usuario_modificacion NUMBER REFERENCES usuarios(id)
);

-- Tabla de Historial de Cheques
CREATE TABLE historial_cheques (
    id NUMBER DEFAULT historial_cheques_seq.NEXTVAL PRIMARY KEY,
    cheque_id NUMBER REFERENCES cheques(id),
    estado_anterior VARCHAR2(10),
    estado_nuevo VARCHAR2(10) NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT SYSTIMESTAMP,
    usuario_id NUMBER REFERENCES usuarios(id),
    observacion VARCHAR2(500)
);

-- Tabla de Historial de Facturas
CREATE TABLE historial_facturas (
    id NUMBER DEFAULT historial_facturas_seq.NEXTVAL PRIMARY KEY,
    factura_id NUMBER REFERENCES facturas(id),
    accion VARCHAR2(20) NOT NULL,
    fecha_accion TIMESTAMP DEFAULT SYSTIMESTAMP,
    usuario_id NUMBER REFERENCES usuarios(id),
    detalles VARCHAR2(500)
);

-- Tabla de Historial de Clientes
CREATE TABLE historial_clientes (
    id NUMBER DEFAULT historial_clientes_seq.NEXTVAL PRIMARY KEY,
    cliente_id NUMBER REFERENCES clientes(id),
    accion VARCHAR2(20) NOT NULL,
    fecha_accion TIMESTAMP DEFAULT SYSTIMESTAMP,
    usuario_id NUMBER REFERENCES usuarios(id),
    detalles VARCHAR2(500)
);

-- Tabla de Log de Accesos
CREATE TABLE log_accesos (
    id NUMBER DEFAULT log_accesos_seq.NEXTVAL PRIMARY KEY,
    usuario_id NUMBER REFERENCES usuarios(id),
    fecha_acceso TIMESTAMP DEFAULT SYSTIMESTAMP,
    fecha_salida TIMESTAMP,
    ip_address VARCHAR2(45)
);

-- Índices para mejorar el rendimiento
CREATE INDEX idx_cheques_cliente ON cheques(cliente_id);
CREATE INDEX idx_cheques_factura ON cheques(factura_id);
CREATE INDEX idx_facturas_cliente ON facturas(cliente_id);
CREATE INDEX idx_historial_cheques_cheque ON historial_cheques(cheque_id);
CREATE INDEX idx_historial_facturas_factura ON historial_facturas(factura_id);
CREATE INDEX idx_historial_clientes_cliente ON historial_clientes(cliente_id);
CREATE INDEX idx_log_accesos_usuario ON log_accesos(usuario_id);

-- Insertar usuario administrador por defecto
-- Password: admin123 (hash generado con password_hash en PHP)
INSERT INTO usuarios (
    username, 
    password_hash, 
    nombre, 
    email, 
    estado
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrador',
    'admin@example.com',
    'A'
);

-- Comentarios en las tablas
COMMENT ON TABLE usuarios IS 'Almacena la información de los usuarios del sistema';
COMMENT ON TABLE clientes IS 'Almacena la información de los clientes';
COMMENT ON TABLE facturas IS 'Almacena las facturas emitidas';
COMMENT ON TABLE cheques IS 'Almacena la información de los cheques';
COMMENT ON TABLE historial_cheques IS 'Registra los cambios de estado de los cheques';
COMMENT ON TABLE historial_facturas IS 'Registra los cambios en las facturas';
COMMENT ON TABLE historial_clientes IS 'Registra los cambios en los clientes';
COMMENT ON TABLE log_accesos IS 'Registra los accesos al sistema';

-- Comentarios en las columnas principales
COMMENT ON COLUMN cheques.estado IS 'Estados posibles: INGRESADO, CRUZADO, DEPOSITADO, ANULADO';
COMMENT ON COLUMN facturas.estado IS 'Estados posibles: A (Activa), C (Cruzada), N (Anulada)';
COMMENT ON COLUMN clientes.estado IS 'Estados posibles: A (Activo), I (Inactivo)';
COMMENT ON COLUMN usuarios.estado IS 'Estados posibles: A (Activo), I (Inactivo)';

-- Crear vistas útiles
CREATE OR REPLACE VIEW v_cheques_pendientes AS
SELECT 
    ch.id,
    ch.numero_cheque,
    ch.beneficiario,
    ch.monto,
    ch.fecha_emision,
    ch.fecha_deposito,
    c.nombre as cliente,
    f.numero as factura
FROM 
    cheques ch
    INNER JOIN clientes c ON ch.cliente_id = c.id
    INNER JOIN facturas f ON ch.factura_id = f.id
WHERE 
    ch.estado IN ('INGRESADO', 'CRUZADO')
    AND ch.fecha_deposito >= SYSDATE;

CREATE OR REPLACE VIEW v_facturas_sin_cheque AS
SELECT 
    f.id,
    f.numero,
    f.fecha,
    f.monto,
    c.nombre as cliente
FROM 
    facturas f
    INNER JOIN clientes c ON f.cliente_id = c.id
    LEFT JOIN cheques ch ON f.id = ch.factura_id
WHERE 
    f.estado = 'A'
    AND ch.id IS NULL;

-- Triggers para auditoría
CREATE OR REPLACE TRIGGER trg_cheques_audit
BEFORE INSERT OR UPDATE ON cheques
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.fecha_registro := SYSTIMESTAMP;
    ELSIF UPDATING THEN
        :NEW.fecha_modificacion := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_facturas_audit
BEFORE INSERT OR UPDATE ON facturas
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.fecha_registro := SYSTIMESTAMP;
    ELSIF UPDATING THEN
        :NEW.fecha_modificacion := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_clientes_audit
BEFORE INSERT OR UPDATE ON clientes
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.fecha_registro := SYSTIMESTAMP;
    ELSIF UPDATING THEN
        :NEW.fecha_modificacion := SYSTIMESTAMP;
    END IF;
END;
/

-- Commit final
COMMIT;