
-- =====================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS: PELUQUERÍA
-- Motor: MariaDB 11.4
-- Juego de caracteres: UTF-8
-- =====================================================================

CREATE DATABASE IF NOT EXISTS peluqueria
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE peluqueria;


-- =====================================================================
-- 1. TABLA: ROLES
-- =====================================================================

CREATE TABLE IF NOT EXISTS ROLES (
    IDRoles INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(50) NOT NULL
);

INSERT INTO ROLES (IDRoles, rol)
VALUES
    (1, 'Administrador'),
    (2, 'Peluquero'),
    (3, 'Profesor')
ON DUPLICATE KEY UPDATE rol = VALUES(rol);


-- =====================================================================
-- 2. TABLA: PELUQUEROS
-- =====================================================================

CREATE TABLE IF NOT EXISTS PELUQUEROS (
    IDPeluquero INT AUTO_INCREMENT PRIMARY KEY,

    Usuario VARCHAR(50) NOT NULL UNIQUE,
    Nombre VARCHAR(50) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    Telefono VARCHAR(20),
    Direccion VARCHAR(255),
    Email VARCHAR(100) UNIQUE,
    Notas TEXT,

    PW VARCHAR(255) NOT NULL,
    Restaurar BOOLEAN DEFAULT FALSE,

    FK_rol INT NOT NULL,

    FechaAlta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaBaja DATETIME NULL,
    baja BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_rol)
        REFERENCES ROLES(IDRoles)
);


-- =====================================================================
-- 3. TABLA: CLIENTES
-- =====================================================================

CREATE TABLE IF NOT EXISTS CLIENTES (
    IDCliente INT AUTO_INCREMENT PRIMARY KEY,

    Nombre VARCHAR(50) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),

    Telefono VARCHAR(20) NULL,
    Direccion VARCHAR(255),
    Email VARCHAR(100),
    Notas TEXT,

    FechaAlta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaBaja DATETIME NULL,

    baja BOOLEAN DEFAULT FALSE
);


-- =====================================================================
-- 4. TABLA: HISTORIALCLIENTES
-- =====================================================================

CREATE TABLE IF NOT EXISTS HISTORIALCLIENTES (
    IDHistorial INT AUTO_INCREMENT PRIMARY KEY,

    observaciones TEXT NOT NULL,

    FechaCreacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    -- Eliminación lógica de las anotaciones
    Eliminado BOOLEAN NOT NULL DEFAULT FALSE,
    FechaEliminacion DATETIME NULL,
    FK_eliminadoPor INT NULL,
    MotivoEliminacion TEXT NULL,

    -- Última modificación
    FechaModificacion DATETIME NULL,
    FK_modificadoPor INT NULL,

    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,

    FOREIGN KEY (FK_Cliente)
        REFERENCES CLIENTES(IDCliente),

    FOREIGN KEY (FK_peluquero)
        REFERENCES PELUQUEROS(IDPeluquero),

    FOREIGN KEY (FK_modificadoPor)
        REFERENCES PELUQUEROS(IDPeluquero),

    FOREIGN KEY (FK_eliminadoPor)
        REFERENCES PELUQUEROS(IDPeluquero),

    INDEX IDX_HISTORIAL_CLIENTE (FK_Cliente),
    INDEX IDX_HISTORIAL_ELIMINADO (Eliminado)
);


-- =====================================================================
-- 4.1. TABLA: HISTORIALCLIENTES_AUDITORIA
-- =====================================================================

CREATE TABLE IF NOT EXISTS HISTORIALCLIENTES_AUDITORIA (
    IDAuditoriaHistorial INT AUTO_INCREMENT PRIMARY KEY,

    FK_Historial INT NOT NULL,

    operacion ENUM(
        'MODIFICACION',
        'ELIMINACION'
    ) NOT NULL,

    observaciones_anteriores TEXT NOT NULL,
    observaciones_nuevas TEXT NULL,

    FK_peluquero INT NOT NULL,

    motivo TEXT NULL,

    FechaOperacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (FK_Historial)
        REFERENCES HISTORIALCLIENTES(IDHistorial),

    FOREIGN KEY (FK_peluquero)
        REFERENCES PELUQUEROS(IDPeluquero),

    INDEX IDX_AUDITORIA_HISTORIAL (FK_Historial),
    INDEX IDX_AUDITORIA_FECHA (FechaOperacion)
);


-- =====================================================================
-- 5. TABLA: SERVICIOS
-- =====================================================================

CREATE TABLE IF NOT EXISTS SERVICIOS (
    IDServicio INT AUTO_INCREMENT PRIMARY KEY,
    servicio VARCHAR(100) NOT NULL
);


-- =====================================================================
-- 6. TABLA: SERVICIOSPRECIOS
-- =====================================================================

CREATE TABLE IF NOT EXISTS SERVICIOSPRECIOS (
    IDServicioprecio INT AUTO_INCREMENT PRIMARY KEY,

    FK_Servicio INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (FK_Servicio)
        REFERENCES SERVICIOS(IDServicio)
        ON DELETE CASCADE
);


-- =====================================================================
-- 7. TABLA: FACTURA
-- =====================================================================

CREATE TABLE IF NOT EXISTS FACTURA (
    IDFactura INT AUTO_INCREMENT PRIMARY KEY,

    Fecha DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,
    FK_Servicio INT NULL,

    Servicio VARCHAR(100) NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    notas TEXT,

    FOREIGN KEY (FK_Cliente)
        REFERENCES CLIENTES(IDCliente),

    FOREIGN KEY (FK_peluquero)
        REFERENCES PELUQUEROS(IDPeluquero),

    FOREIGN KEY (FK_Servicio)
        REFERENCES SERVICIOS(IDServicio)
        ON DELETE SET NULL
);


-- =====================================================================
-- 8. TABLA: AGENDA
-- =====================================================================

CREATE TABLE IF NOT EXISTS AGENDA (
    IDAgenda INT AUTO_INCREMENT PRIMARY KEY,

    Fecha DATE NOT NULL,
    Hora TIME NOT NULL,

    FK_Cliente INT NOT NULL,
    FK_peluquero INT NOT NULL,

    FOREIGN KEY (FK_Cliente)
        REFERENCES CLIENTES(IDCliente),

    FOREIGN KEY (FK_peluquero)
        REFERENCES PELUQUEROS(IDPeluquero)
);


-- =====================================================================
-- 9. TABLA: AUDITORIA
-- =====================================================================

CREATE TABLE IF NOT EXISTS AUDITORIA (
    IDAuditoria INT AUTO_INCREMENT PRIMARY KEY,

    tabla_afectada VARCHAR(50) NOT NULL,
    operacion VARCHAR(20) NOT NULL,
    registro_id INT NOT NULL,

    FK_peluquero INT NULL,

    detalles MEDIUMTEXT NOT NULL,

    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- =====================================================================
-- 10. TABLA: RESTABLECIMIENTOS
-- =====================================================================

CREATE TABLE IF NOT EXISTS RESTABLECIMIENTOS (
    IDRestablecimiento INT AUTO_INCREMENT PRIMARY KEY,

    FK_Peluquero INT NOT NULL,

    Token VARCHAR(255) NOT NULL,

    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaExpiracion DATETIME NOT NULL,

    Utilizado BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_Peluquero)
        REFERENCES PELUQUEROS(IDPeluquero)
);


-- =====================================================================
-- 11. TABLA: TOKENS_ACCESO
-- =====================================================================

CREATE TABLE IF NOT EXISTS TOKENS_ACCESO (
    IDToken INT AUTO_INCREMENT PRIMARY KEY,

    FK_Peluquero INT NOT NULL,

    TokenHash CHAR(64) NOT NULL UNIQUE,

    FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaExpiracion DATETIME NOT NULL,

    Revocado BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (FK_Peluquero)
        REFERENCES PELUQUEROS(IDPeluquero)
);


-- =====================================================================
-- 12. TABLA: FICHAS_TRATAMIENTO
-- =====================================================================

CREATE TABLE IF NOT EXISTS FICHAS_TRATAMIENTO (

    IDFicha INT AUTO_INCREMENT PRIMARY KEY,

    -- -------------------------------------------------------------
    -- ESTADO Y CICLO DE VIDA
    -- -------------------------------------------------------------

    Estado ENUM(
        'borrador',
        'enviada',
        'finalizada'
    ) NOT NULL DEFAULT 'borrador',

    -- Indica si la ficha ha sido finalizada alguna vez.
    -- Una vez activado, no podrá volver a FALSE.
    FueFinalizada BOOLEAN NOT NULL DEFAULT FALSE,

    FechaCreacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    FechaEnvio DATETIME NULL,
    FechaFinalizacion DATETIME NULL,
    FechaTratamiento DATETIME NULL,
    FechaModificacion DATETIME NULL,

    -- -------------------------------------------------------------
    -- RESPONSABLES
    -- -------------------------------------------------------------

    FK_Cliente INT NOT NULL,
    FK_Peluquero INT NOT NULL,
    FK_Profesor INT NULL,
    FK_modificadoPor INT NULL,

    -- -------------------------------------------------------------
    -- 1. ENTREVISTA Y DIAGNÓSTICO
    -- -------------------------------------------------------------

    ColorCompleto BOOLEAN NOT NULL DEFAULT FALSE,
    Raiz BOOLEAN NOT NULL DEFAULT FALSE,
    Mechas BOOLEAN NOT NULL DEFAULT FALSE,
    Matiz BOOLEAN NOT NULL DEFAULT FALSE,

    OtroServicio VARCHAR(255),
    UltimoColorProcesos TEXT,

    -- Alergias:
    -- 0 = No
    -- 1 = Sí
    -- 2 = No sabe
    -- NULL = Sin responder

    Alergias TINYINT UNSIGNED NULL,
    AlergiasDetalles TEXT,

    CueroCabelludoAlteraciones TEXT,

    Longitud VARCHAR(50),
    RaizCrecidaCm DECIMAL(5,2),

    Grosor VARCHAR(20),

    -- Porcentaje de canas: de 0 a 100
    Canas TINYINT UNSIGNED NULL,

    Distribucion VARCHAR(255),

    -- Altura de tono y reflejos actuales

    ZonaRaizTonoReflejos VARCHAR(255),
    ZonaMediosTonoReflejos VARCHAR(255),
    ZonaPuntasTonoReflejos VARCHAR(255),

    -- Porosidad y resistencia

    ZonaRaizPorosidadResistencia TEXT,
    ZonaMediosPorosidadResistencia TEXT,
    ZonaPuntasPorosidadResistencia TEXT,

    -- -------------------------------------------------------------
    -- 2. COMPROBACIONES Y PROPUESTA
    -- -------------------------------------------------------------

    PruebaAlergia TEXT,
    PruebaMechon TEXT,

    ObjetivoAltura VARCHAR(255),
    ObjetivoReflejo VARCHAR(255),
    ObjetivoFondoPrevisto VARCHAR(255),

    TecnicaJustificacion TEXT,

    -- Visto bueno docente:
    -- 1 = Proceder
    -- 2 = Adaptar
    -- 3 = Posponer
    -- NULL = Sin responder

    VistoBuenoDocente TINYINT UNSIGNED NULL,

    -- -------------------------------------------------------------
    -- 3. FÓRMULA DE COLOR Y PRODUCTOS
    -- -------------------------------------------------------------

    MarcaLinea VARCHAR(255),
    PreparacionPrevia TEXT,
    OrdenAplicacion TEXT,

    -- -------------------------------------------------------------
    -- 4. APLICACIÓN Y RESULTADO
    -- -------------------------------------------------------------

    InicioAplicacion DATETIME NULL,
    RetiradaAplicacion DATETIME NULL,

    TiempoAplicacionMinutos INT UNSIGNED NULL,

    IncidenciasAjustesTratamientoFinal TEXT,

    TonoReflejoFinal VARCHAR(255),

    -- Canas cubiertas:
    -- 1 = Sí
    -- 2 = Parcial
    -- 3 = No procede
    -- NULL = Sin responder

    CanasCubiertas TINYINT UNSIGNED NULL,

    Uniforme BOOLEAN NOT NULL DEFAULT FALSE,

    -- -------------------------------------------------------------
    -- 5. VALORACIÓN Y MANTENIMIENTO
    -- -------------------------------------------------------------

    CuidadosMantenimiento TEXT,
    QueSalioBienMejoraria TEXT,
    ValoracionRevisionFinalDocente TEXT,

    -- -------------------------------------------------------------
    -- RELACIONES
    -- -------------------------------------------------------------

    FOREIGN KEY (FK_Cliente)
        REFERENCES CLIENTES(IDCliente),

    FOREIGN KEY (FK_Peluquero)
        REFERENCES PELUQUEROS(IDPeluquero),

    FOREIGN KEY (FK_Profesor)
        REFERENCES PELUQUEROS(IDPeluquero),

    FOREIGN KEY (FK_modificadoPor)
        REFERENCES PELUQUEROS(IDPeluquero),

    -- -------------------------------------------------------------
    -- ÍNDICES
    -- -------------------------------------------------------------

    INDEX IDX_FICHA_CLIENTE (FK_Cliente),
    INDEX IDX_FICHA_PELUQUERO (FK_Peluquero),
    INDEX IDX_FICHA_PROFESOR (FK_Profesor),
    INDEX IDX_FICHA_MODIFICADO (FK_modificadoPor),
    INDEX IDX_FICHA_FECHA (FechaTratamiento),
    INDEX IDX_FICHA_ESTADO (Estado),
    INDEX IDX_FICHA_FINALIZADA (FueFinalizada),

    -- -------------------------------------------------------------
    -- VALIDACIONES
    -- -------------------------------------------------------------

    CONSTRAINT CHK_FICHA_ALERGIAS
        CHECK (
            Alergias IS NULL
            OR Alergias IN (0, 1, 2)
        ),

    CONSTRAINT CHK_FICHA_CANAS
        CHECK (
            Canas IS NULL
            OR Canas <= 100
        ),

    CONSTRAINT CHK_FICHA_VISTO_BUENO
        CHECK (
            VistoBuenoDocente IS NULL
            OR VistoBuenoDocente IN (1, 2, 3)
        ),

    CONSTRAINT CHK_FICHA_CANAS_CUBIERTAS
        CHECK (
            CanasCubiertas IS NULL
            OR CanasCubiertas IN (1, 2, 3)
        ),

    CONSTRAINT CHK_FICHA_RAIZ_CRECIDA
        CHECK (
            RaizCrecidaCm IS NULL
            OR RaizCrecidaCm >= 0
        )
);


-- =====================================================================
-- 13. TABLA: FORMULAS_TRATAMIENTO
-- =====================================================================

CREATE TABLE IF NOT EXISTS FORMULAS_TRATAMIENTO (

    IDFormula INT AUTO_INCREMENT PRIMARY KEY,

    FK_Ficha INT NOT NULL,

    ZonaFase ENUM(
        'Raiz',
        'Medios y puntas',
        'Mechas',
        'Matiz'
    ) NOT NULL,

    Tono VARCHAR(100),

    Gramos DECIMAL(8,2),

    Oxidante VARCHAR(100),

    CantidadOxidante DECIMAL(8,2),

    Proporcion VARCHAR(50),

    Tiempo VARCHAR(50),

    -- No se permite eliminar automáticamente las fórmulas
    -- cuando se elimina una ficha.
    -- El backend tendrá que eliminarlas expresamente.

    FOREIGN KEY (FK_Ficha)
        REFERENCES FICHAS_TRATAMIENTO(IDFicha)
        ON DELETE RESTRICT,

    INDEX IDX_FORMULA_FICHA (FK_Ficha),

    CONSTRAINT CHK_FORMULA_GRAMOS
        CHECK (
            Gramos IS NULL
            OR Gramos >= 0
        ),

    CONSTRAINT CHK_FORMULA_OXIDANTE
        CHECK (
            CantidadOxidante IS NULL
            OR CantidadOxidante >= 0
        )
);


-- =====================================================================
-- 14. TABLA: VERSIONES_FICHAS_TRATAMIENTO
-- =====================================================================

CREATE TABLE IF NOT EXISTS VERSIONES_FICHAS_TRATAMIENTO (

    IDVersion INT AUTO_INCREMENT PRIMARY KEY,

    FK_Ficha INT NOT NULL,

    NumeroVersion INT UNSIGNED NOT NULL,

    -- Fotografía completa de los datos de la ficha.
    DatosFicha JSON NOT NULL,

    -- Fotografía completa de las fórmulas.
    DatosFormulas JSON NOT NULL,

    FechaVersion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    FK_finalizadoPor INT NOT NULL,

    FOREIGN KEY (FK_Ficha)
        REFERENCES FICHAS_TRATAMIENTO(IDFicha)
        ON DELETE RESTRICT,

    FOREIGN KEY (FK_finalizadoPor)
        REFERENCES PELUQUEROS(IDPeluquero),

    -- No puede haber dos versiones con el mismo número
    -- dentro de una misma ficha.

    UNIQUE KEY UK_VERSION_FICHA_NUMERO (
        FK_Ficha,
        NumeroVersion
    ),

    INDEX IDX_VERSION_FECHA (FechaVersion),
    INDEX IDX_VERSION_USUARIO (FK_finalizadoPor)
);


-- =====================================================================
-- 15. TABLA: HISTORIAL_FICHAS_TRATAMIENTO
-- =====================================================================

CREATE TABLE IF NOT EXISTS HISTORIAL_FICHAS_TRATAMIENTO (

    IDHistorialFicha INT AUTO_INCREMENT PRIMARY KEY,

    -- Conserva el ID original aunque se elimine la ficha.

    IDFichaReferencia INT NOT NULL,

    -- Referencia a la ficha mientras exista.
    -- Al eliminar físicamente la ficha, pasa a NULL.

    FK_Ficha INT NULL,

    Operacion ENUM(
        'CREACION',
        'MODIFICACION',
        'ENVIO',
        'DEVOLUCION',
        'FINALIZACION',
        'REAPERTURA',
        'ELIMINACION'
    ) NOT NULL,

    EstadoAnterior ENUM(
        'borrador',
        'enviada',
        'finalizada'
    ) NULL,

    EstadoNuevo ENUM(
        'borrador',
        'enviada',
        'finalizada'
    ) NULL,

    -- Datos anteriores y nuevos de la operación.
    -- El backend decidirá cuándo guardar estos datos.

    DatosAnteriores JSON NULL,
    DatosNuevos JSON NULL,

    Motivo TEXT NULL,

    FechaOperacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    FK_peluquero INT NOT NULL,

    FOREIGN KEY (FK_Ficha)
        REFERENCES FICHAS_TRATAMIENTO(IDFicha)
        ON DELETE SET NULL,

    FOREIGN KEY (FK_peluquero)
        REFERENCES PELUQUEROS(IDPeluquero),

    INDEX IDX_HISTORIAL_FICHA (IDFichaReferencia),
    INDEX IDX_HISTORIAL_FECHA (FechaOperacion),
    INDEX IDX_HISTORIAL_OPERACION (Operacion),
    INDEX IDX_HISTORIAL_USUARIO (FK_peluquero)
);


-- =====================================================================
-- TRIGGERS DE AUDITORÍA
-- =====================================================================

DELIMITER //


-- =====================================================================
-- 1. TRIGGERS DE CLIENTES
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1.1. INSERT EN CLIENTES
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_clientes_insert
AFTER INSERT ON CLIENTES
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'CLIENTES',
        'INSERT',
        NEW.IDCliente,
        @usuario_actual,
        CONCAT(
            'Nuevo cliente dado de alta: ',
            NEW.Nombre,
            ' ',
            NEW.Apellido1
        )
    );
END//


-- ---------------------------------------------------------------------
-- 1.2. UPDATE EN CLIENTES
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_clientes_update
AFTER UPDATE ON CLIENTES
FOR EACH ROW
BEGIN
    DECLARE cambios TEXT DEFAULT '';

    IF NOT (OLD.Nombre <=> NEW.Nombre) THEN
        SET cambios = CONCAT(
            cambios,
            'Nombre: [', OLD.Nombre, ' -> ', NEW.Nombre, '] '
        );
    END IF;

    IF NOT (OLD.Apellido1 <=> NEW.Apellido1) THEN
        SET cambios = CONCAT(
            cambios,
            'Apellido1: [', OLD.Apellido1, ' -> ', NEW.Apellido1, '] '
        );
    END IF;

    IF NOT (OLD.Telefono <=> NEW.Telefono) THEN
        SET cambios = CONCAT(
            cambios,
            'Teléfono: [', OLD.Telefono, ' -> ', NEW.Telefono, '] '
        );
    END IF;

    IF NOT (OLD.Email <=> NEW.Email) THEN
        SET cambios = CONCAT(
            cambios,
            'Email: [', OLD.Email, ' -> ', NEW.Email, '] '
        );
    END IF;

    IF NOT (OLD.baja <=> NEW.baja) THEN
        IF NEW.baja = 1 THEN
            SET cambios = CONCAT(
                cambios,
                'Estado: [Dado de Baja] '
            );
        ELSE
            SET cambios = CONCAT(
                cambios,
                'Estado: [Reactivado] '
            );
        END IF;
    END IF;

    IF cambios <> '' THEN
        INSERT INTO AUDITORIA (
            tabla_afectada,
            operacion,
            registro_id,
            FK_peluquero,
            detalles
        )
        VALUES (
            'CLIENTES',
            'UPDATE',
            NEW.IDCliente,
            @usuario_actual,
            CONCAT(
                'Modificación en cliente ID ',
                NEW.IDCliente,
                '. Cambios -> ',
                cambios
            )
        );
    END IF;
END//


-- =====================================================================
-- 2. TRIGGERS DE FICHAS DE TRATAMIENTO
-- =====================================================================

-- ---------------------------------------------------------------------
-- 2.1. INSERT EN FICHAS_TRATAMIENTO
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_tratamiento_insert
AFTER INSERT ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FICHAS_TRATAMIENTO',
        'INSERT',
        NEW.IDFicha,
        @usuario_actual,
        CONCAT(
            'Creación de ficha de tratamiento. ',
            'Cliente: ', NEW.FK_Cliente,
            '. Peluquero responsable: ', NEW.FK_Peluquero,
            '. Profesor: ',
            COALESCE(NEW.FK_Profesor, 'Sin asignar'),
            '. Estado: ', NEW.Estado
        )
    );
END//


-- ---------------------------------------------------------------------
-- 2.2. UPDATE EN FICHAS_TRATAMIENTO
-- ---------------------------------------------------------------------
-- La auditoría general registra los cambios principales.
-- El historial funcional detallado se registrará desde PHP.
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_tratamiento_update
AFTER UPDATE ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FICHAS_TRATAMIENTO',

        CASE
            WHEN OLD.Estado <> NEW.Estado
                 AND NEW.Estado = 'finalizada'
            THEN 'FINALIZAR'

            WHEN OLD.Estado <> NEW.Estado
                 AND NEW.Estado = 'enviada'
            THEN 'ENVIO'

            WHEN OLD.Estado <> NEW.Estado
                 AND NEW.Estado = 'borrador'
            THEN 'CAMBIO_BORRADOR'

            ELSE 'UPDATE'
        END,

        NEW.IDFicha,

        @usuario_actual,

        CONCAT(
            'Modificación de ficha de tratamiento. ',

            'ANTES: ',
            JSON_OBJECT(
                'Estado', OLD.Estado,
                'FueFinalizada', OLD.FueFinalizada,
                'FechaEnvio', OLD.FechaEnvio,
                'FechaFinalizacion', OLD.FechaFinalizacion,
                'FechaTratamiento', OLD.FechaTratamiento,
                'FechaModificacion', OLD.FechaModificacion,
                'FK_Cliente', OLD.FK_Cliente,
                'FK_Peluquero', OLD.FK_Peluquero,
                'FK_Profesor', OLD.FK_Profesor,
                'FK_modificadoPor', OLD.FK_modificadoPor,

                'ColorCompleto', OLD.ColorCompleto,
                'Raiz', OLD.Raiz,
                'Mechas', OLD.Mechas,
                'Matiz', OLD.Matiz,
                'OtroServicio', OLD.OtroServicio,
                'UltimoColorProcesos', OLD.UltimoColorProcesos,

                'Alergias', OLD.Alergias,
                'AlergiasDetalles', OLD.AlergiasDetalles,
                'CueroCabelludoAlteraciones',
                    OLD.CueroCabelludoAlteraciones,

                'Longitud', OLD.Longitud,
                'RaizCrecidaCm', OLD.RaizCrecidaCm,
                'Grosor', OLD.Grosor,
                'Canas', OLD.Canas,
                'Distribucion', OLD.Distribucion,

                'ZonaRaizTonoReflejos',
                    OLD.ZonaRaizTonoReflejos,
                'ZonaMediosTonoReflejos',
                    OLD.ZonaMediosTonoReflejos,
                'ZonaPuntasTonoReflejos',
                    OLD.ZonaPuntasTonoReflejos,

                'ZonaRaizPorosidadResistencia',
                    OLD.ZonaRaizPorosidadResistencia,
                'ZonaMediosPorosidadResistencia',
                    OLD.ZonaMediosPorosidadResistencia,
                'ZonaPuntasPorosidadResistencia',
                    OLD.ZonaPuntasPorosidadResistencia,

                'PruebaAlergia', OLD.PruebaAlergia,
                'PruebaMechon', OLD.PruebaMechon,

                'ObjetivoAltura', OLD.ObjetivoAltura,
                'ObjetivoReflejo', OLD.ObjetivoReflejo,
                'ObjetivoFondoPrevisto',
                    OLD.ObjetivoFondoPrevisto,

                'TecnicaJustificacion',
                    OLD.TecnicaJustificacion,
                'VistoBuenoDocente', OLD.VistoBuenoDocente,

                'MarcaLinea', OLD.MarcaLinea,
                'PreparacionPrevia', OLD.PreparacionPrevia,
                'OrdenAplicacion', OLD.OrdenAplicacion,

                'InicioAplicacion', OLD.InicioAplicacion,
                'RetiradaAplicacion', OLD.RetiradaAplicacion,
                'TiempoAplicacionMinutos',
                    OLD.TiempoAplicacionMinutos,

                'IncidenciasAjustesTratamientoFinal',
                    OLD.IncidenciasAjustesTratamientoFinal,

                'TonoReflejoFinal', OLD.TonoReflejoFinal,
                'CanasCubiertas', OLD.CanasCubiertas,
                'Uniforme', OLD.Uniforme,

                'CuidadosMantenimiento',
                    OLD.CuidadosMantenimiento,

                'QueSalioBienMejoraria',
                    OLD.QueSalioBienMejoraria,

                'ValoracionRevisionFinalDocente',
                    OLD.ValoracionRevisionFinalDocente
            ),

            ' | DESPUÉS: ',

            JSON_OBJECT(
                'Estado', NEW.Estado,
                'FueFinalizada', NEW.FueFinalizada,
                'FechaEnvio', NEW.FechaEnvio,
                'FechaFinalizacion', NEW.FechaFinalizacion,
                'FechaTratamiento', NEW.FechaTratamiento,
                'FechaModificacion', NEW.FechaModificacion,
                'FK_Cliente', NEW.FK_Cliente,
                'FK_Peluquero', NEW.FK_Peluquero,
                'FK_Profesor', NEW.FK_Profesor,
                'FK_modificadoPor', NEW.FK_modificadoPor,

                'ColorCompleto', NEW.ColorCompleto,
                'Raiz', NEW.Raiz,
                'Mechas', NEW.Mechas,
                'Matiz', NEW.Matiz,
                'OtroServicio', NEW.OtroServicio,
                'UltimoColorProcesos', NEW.UltimoColorProcesos,

                'Alergias', NEW.Alergias,
                'AlergiasDetalles', NEW.AlergiasDetalles,
                'CueroCabelludoAlteraciones',
                    NEW.CueroCabelludoAlteraciones,

                'Longitud', NEW.Longitud,
                'RaizCrecidaCm', NEW.RaizCrecidaCm,
                'Grosor', NEW.Grosor,
                'Canas', NEW.Canas,
                'Distribucion', NEW.Distribucion,

                'ZonaRaizTonoReflejos',
                    NEW.ZonaRaizTonoReflejos,
                'ZonaMediosTonoReflejos',
                    NEW.ZonaMediosTonoReflejos,
                'ZonaPuntasTonoReflejos',
                    NEW.ZonaPuntasTonoReflejos,

                'ZonaRaizPorosidadResistencia',
                    NEW.ZonaRaizPorosidadResistencia,
                'ZonaMediosPorosidadResistencia',
                    NEW.ZonaMediosPorosidadResistencia,
                'ZonaPuntasPorosidadResistencia',
                    NEW.ZonaPuntasPorosidadResistencia,

                'PruebaAlergia', NEW.PruebaAlergia,
                'PruebaMechon', NEW.PruebaMechon,

                'ObjetivoAltura', NEW.ObjetivoAltura,
                'ObjetivoReflejo', NEW.ObjetivoReflejo,
                'ObjetivoFondoPrevisto',
                    NEW.ObjetivoFondoPrevisto,

                'TecnicaJustificacion',
                    NEW.TecnicaJustificacion,
                'VistoBuenoDocente', NEW.VistoBuenoDocente,

                'MarcaLinea', NEW.MarcaLinea,
                'PreparacionPrevia', NEW.PreparacionPrevia,
                'OrdenAplicacion', NEW.OrdenAplicacion,

                'InicioAplicacion', NEW.InicioAplicacion,
                'RetiradaAplicacion', NEW.RetiradaAplicacion,
                'TiempoAplicacionMinutos',
                    NEW.TiempoAplicacionMinutos,

                'IncidenciasAjustesTratamientoFinal',
                    NEW.IncidenciasAjustesTratamientoFinal,

                'TonoReflejoFinal', NEW.TonoReflejoFinal,
                'CanasCubiertas', NEW.CanasCubiertas,
                'Uniforme', NEW.Uniforme,

                'CuidadosMantenimiento',
                    NEW.CuidadosMantenimiento,

                'QueSalioBienMejoraria',
                    NEW.QueSalioBienMejoraria,

                'ValoracionRevisionFinalDocente',
                    NEW.ValoracionRevisionFinalDocente
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 2.3. DELETE EN FICHAS_TRATAMIENTO
-- ---------------------------------------------------------------------
-- Solo se permitirá eliminar borradores nunca finalizados.
-- El backend registrará previamente la operación en el historial.
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_tratamiento_delete
AFTER DELETE ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FICHAS_TRATAMIENTO',
        'DELETE',
        OLD.IDFicha,
        @usuario_actual,
        CONCAT(
            'Eliminación física de borrador. ',
            'Cliente: ', OLD.FK_Cliente,
            '. Peluquero: ', OLD.FK_Peluquero,
            '. Estado anterior: ', OLD.Estado,
            '. FueFinalizada: ', OLD.FueFinalizada
        )
    );
END//


-- ---------------------------------------------------------------------
-- 2.4. IMPEDIR QUE UNA FICHA PIERDA SU CONDICIÓN DE FINALIZADA
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_fichas_no_desfinalizar
BEFORE UPDATE ON FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    IF OLD.FueFinalizada = TRUE
       AND NEW.FueFinalizada = FALSE
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Una ficha que fue finalizada no puede perder esa condición';
    END IF;
END//


-- =====================================================================
-- 3. TRIGGERS DE FORMULAS_TRATAMIENTO
-- =====================================================================

-- ---------------------------------------------------------------------
-- 3.1. INSERT EN FORMULAS_TRATAMIENTO
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_insert
AFTER INSERT ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'INSERT',
        NEW.IDFormula,
        @usuario_actual,
        CONCAT(
            'Nueva fórmula. Ficha: ', NEW.FK_Ficha,
            '. Zona: ', NEW.ZonaFase,
            '. Datos: ',
            JSON_OBJECT(
                'Tono', NEW.Tono,
                'Gramos', NEW.Gramos,
                'Oxidante', NEW.Oxidante,
                'CantidadOxidante', NEW.CantidadOxidante,
                'Proporcion', NEW.Proporcion,
                'Tiempo', NEW.Tiempo
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 3.2. UPDATE EN FORMULAS_TRATAMIENTO
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_update
AFTER UPDATE ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'UPDATE',
        NEW.IDFormula,
        @usuario_actual,
        CONCAT(
            'Modificación de fórmula de la ficha ',
            NEW.FK_Ficha,

            '. ANTES: ',

            JSON_OBJECT(
                'FK_Ficha', OLD.FK_Ficha,
                'ZonaFase', OLD.ZonaFase,
                'Tono', OLD.Tono,
                'Gramos', OLD.Gramos,
                'Oxidante', OLD.Oxidante,
                'CantidadOxidante', OLD.CantidadOxidante,
                'Proporcion', OLD.Proporcion,
                'Tiempo', OLD.Tiempo
            ),

            ' | DESPUÉS: ',

            JSON_OBJECT(
                'FK_Ficha', NEW.FK_Ficha,
                'ZonaFase', NEW.ZonaFase,
                'Tono', NEW.Tono,
                'Gramos', NEW.Gramos,
                'Oxidante', NEW.Oxidante,
                'CantidadOxidante', NEW.CantidadOxidante,
                'Proporcion', NEW.Proporcion,
                'Tiempo', NEW.Tiempo
            )
        )
    );
END//


-- ---------------------------------------------------------------------
-- 3.3. DELETE EN FORMULAS_TRATAMIENTO
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_formulas_tratamiento_delete
AFTER DELETE ON FORMULAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (
        tabla_afectada,
        operacion,
        registro_id,
        FK_peluquero,
        detalles
    )
    VALUES (
        'FORMULAS_TRATAMIENTO',
        'DELETE',
        OLD.IDFormula,
        @usuario_actual,
        CONCAT(
            'Eliminación de fórmula de la ficha ',
            OLD.FK_Ficha,

            '. Datos eliminados: ',

            JSON_OBJECT(
                'ZonaFase', OLD.ZonaFase,
                'Tono', OLD.Tono,
                'Gramos', OLD.Gramos,
                'Oxidante', OLD.Oxidante,
                'CantidadOxidante', OLD.CantidadOxidante,
                'Proporcion', OLD.Proporcion,
                'Tiempo', OLD.Tiempo
            )
        )
    );
END//


-- =====================================================================
-- 4. PROTECCIÓN DE VERSIONES DE FICHAS FINALIZADAS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 4.1. IMPEDIR MODIFICACIONES DE VERSIONES
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_versiones_ficha_no_update
BEFORE UPDATE ON VERSIONES_FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'No se permite modificar una versión finalizada de una ficha';
END//


-- ---------------------------------------------------------------------
-- 4.2. IMPEDIR ELIMINACIONES DE VERSIONES
-- ---------------------------------------------------------------------

CREATE TRIGGER trg_versiones_ficha_no_delete
BEFORE DELETE ON VERSIONES_FICHAS_TRATAMIENTO
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'No se permite eliminar una versión finalizada de una ficha';
END//


-- =====================================================================
-- FIN DE LOS TRIGGERS
-- =====================================================================

DELIMITER ;
