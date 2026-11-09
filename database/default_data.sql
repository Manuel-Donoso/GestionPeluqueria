USE peluqueria;

-- Desactivar temporalmente las restricciones de claves foráneas para limpiar en cualquier orden
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Limpiar todas las tablas del sistema
DELETE FROM TOKENS_ACCESO;
DELETE FROM RESTABLECIMIENTOS;
DELETE FROM AUDITORIA;
DELETE FROM FORMULAS_TRATAMIENTO;
DELETE FROM FICHAS_TRATAMIENTO;
DELETE FROM AGENDA;
DELETE FROM FACTURA;
DELETE FROM HISTORIALCLIENTES;
DELETE FROM SERVICIOSPRECIOS;
DELETE FROM SERVICIOS;
DELETE FROM CLIENTES;
DELETE FROM PELUQUEROS;
DELETE FROM ROLES;

-- Reactivar las restricciones de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Reiniciar los contadores AUTO_INCREMENT
ALTER TABLE ROLES AUTO_INCREMENT = 1;
ALTER TABLE PELUQUEROS AUTO_INCREMENT = 1;
ALTER TABLE CLIENTES AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOS AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOSPRECIOS AUTO_INCREMENT = 1;
ALTER TABLE HISTORIALCLIENTES AUTO_INCREMENT = 1;
ALTER TABLE FACTURA AUTO_INCREMENT = 1;
ALTER TABLE AGENDA AUTO_INCREMENT = 1;
ALTER TABLE FICHAS_TRATAMIENTO AUTO_INCREMENT = 1;
ALTER TABLE FORMULAS_TRATAMIENTO AUTO_INCREMENT = 1;
ALTER TABLE AUDITORIA AUTO_INCREMENT = 1;
ALTER TABLE RESTABLECIMIENTOS AUTO_INCREMENT = 1;
ALTER TABLE TOKENS_ACCESO AUTO_INCREMENT = 1;

-- 3. Volver a insertar los datos de prueba

-- ---------------------------------------------------------------------
-- 0. ROLES (Requerido por los FK de Peluqueros)
-- ---------------------------------------------------------------------
INSERT INTO ROLES (IDRoles, rol)
VALUES
    (1, 'Administrador'),
    (2, 'Peluquero'),
    (3, 'Profesor')
ON DUPLICATE KEY UPDATE rol = VALUES(rol);

-- ---------------------------------------------------------------------
-- 1. PELUQUEROS
-- ---------------------------------------------------------------------
INSERT INTO PELUQUEROS (Usuario, Nombre, Apellido1, Apellido2, Telefono, Direccion, Email, Notas, PW, Restaurar, FK_rol, FechaAlta, FechaBaja, baja) VALUES
('CarAdP','Carlos', 'Administrador', 'Pérez', '600111222', 'Calle Mayor 1, Madrid', 'admin@peluqueria.com', 'Administrador principal del sistema.', '$2y$12$.bC..uVBaR.FuajruOj3vehy1mgn9q.MFypF8SiwdAHE5/MQ8uBR2', 0, 1, '2026-01-01 08:00:00', NULL, 0),
('LauGarc','Laura', 'García', 'Sanz', '611222333', 'Avenida de América 45, Madrid', 'laura@peluqueria.com', 'Especialista en coloración y mechas.', '$2y$12$.bC..uVBaR.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 0, 2, '2026-01-10 09:00:00', NULL, 0),
('MigFern','Miguel', 'Fernández', 'López', '622333444', 'Calle Toledo 12, Madrid', 'miguel@peluqueria.com', 'Especialista en cortes modernos y barbería.', '$2y$12$.bC..uVBaR.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 0, 2, '2026-02-01 09:00:00', NULL, 0);

-- ---------------------------------------------------------------------
-- 2. CLIENTES
-- ---------------------------------------------------------------------
INSERT INTO CLIENTES (Nombre, Apellido1, Apellido2, Telefono, Direccion, Email, Notas, FechaAlta, FechaBaja, baja) VALUES
('Ana', 'Martínez', 'Ruiz', '633444555', 'Calle Goya 8, Madrid', 'ana.martinez@email.com', 'Prefiere productos sin amoníaco.', '2026-02-10 10:30:00', NULL, 0),
('David', 'Rodríguez', 'Gómez', '644555666', 'Calle Serrano 22, Madrid', 'david.rg@email.com', 'Corte clásico cada 3 semanas.', '2026-02-12 11:15:00', NULL, 0),
('Sofia', 'Alonso', 'Blanco', '655666777', 'Paseo de la Castellana 80, Madrid', 'sofia.alonso@email.com', 'Alérgica a ciertos tintes temporales.', '2026-02-15 16:00:00', NULL, 0),
('Javier', 'Jiménez', 'Pascual', '666777888', 'Calle Alcalá 150, Madrid', 'javier.jp@email.com', 'Cliente habitual de los sábados.', '2026-02-20 12:00:00', NULL, 0);

-- ---------------------------------------------------------------------
-- 3. SERVICIOS Y SERVICIOSPRECIOS
-- ---------------------------------------------------------------------
INSERT INTO SERVICIOS (servicio) VALUES
('Corte de Cabello'),          -- ID 1
('Tinte / Coloración'),        -- ID 2
('Mechas Balayage'),           -- ID 3
('Lavado y Peinado'),          -- ID 4
('Arreglo de Barba');          -- ID 5

INSERT INTO SERVICIOSPRECIOS (FK_Servicio, precio) VALUES
(1, 15.00),
(2, 35.00),
(3, 55.00),
(4, 20.00),
(5, 12.00);

-- ---------------------------------------------------------------------
-- 4. HISTORIAL DE CLIENTES
-- ---------------------------------------------------------------------
INSERT INTO HISTORIALCLIENTES (observaciones, FK_Cliente, FK_peluquero) VALUES
('Se realizó corte degradado y marcaje de barba sin incidencias.', 2, 3),
('Aplicación de tinte castaño claro y peinado ondas suaves.', 1, 2);

-- ---------------------------------------------------------------------
-- 5. FACTURAS (Con el FK_Servicio integrado y precio congelado)
-- ---------------------------------------------------------------------
INSERT INTO FACTURA (Fecha, FK_Cliente, FK_peluquero, FK_Servicio, Servicio, Precio, notas) VALUES
('2026-02-10 11:30:00', 1, 2, 2, 'Tinte / Coloración', 35.00, 'Pago con tarjeta.'),
('2026-02-12 12:00:00', 2, 3, 1, 'Corte de Cabello', 15.00, 'Pago en efectivo.'),
('2026-02-15 17:15:00', 3, 2, 4, 'Lavado y Peinado', 20.00, 'Cliente muy satisfecho.');

-- ---------------------------------------------------------------------
-- 6. AGENDA (Citas programadas)
-- ---------------------------------------------------------------------
INSERT INTO AGENDA (Fecha, Hora, FK_Cliente, FK_peluquero) VALUES
('2026-11-02', '10:00:00', 1, 2),
('2026-11-02', '11:00:00', 2, 3),
('2026-11-03', '16:30:00', 3, 2);


INSERT INTO `FICHAS_TRATAMIENTO` (
    `Estado`,
    `FechaCreacion`,
    `FechaFinalizacion`,
    `FechaTratamiento`,
    `FK_Cliente`,
    `FK_Peluquero`,
    `FK_Profesor`,
    `ColorCompleto`,
    `Raiz`,
    `Mechas`,
    `Matiz`,
    `OtroServicio`,
    `UltimoColorProcesos`,
    `Alergias`,
    `AlergiasDetalles`,
    `CueroCabelludoAlteraciones`,
    `Longitud`,
    `RaizCrecidaCm`,
    `Grosor`,
    `Canas`,
    `Distribucion`,
    `ZonaRaizTonoReflejos`,
    `ZonaMediosTonoReflejos`,
    `ZonaPuntasTonoReflejos`,
    `ZonaRaizPorosidadResistencia`,
    `ZonaMediosPorosidadResistencia`,
    `ZonaPuntasPorosidadResistencia`,
    `PruebaAlergia`,
    `PruebaMechon`,
    `ObjetivoAltura`,
    `ObjetivoReflejo`,
    `ObjetivoFondoPrevisto`,
    `TecnicaJustificacion`,
    `VistoBuenoDocente`,
    `MarcaLinea`,
    `PreparacionPrevia`,
    `OrdenAplicacion`,
    `InicioAplicacion`,
    `RetiradaAplicacion`,
    `TiempoAplicacionMinutos`,
    `IncidenciasAjustesTratamientoFinal`,
    `TonoReflejoFinal`,
    `CanasCubiertas`,
    `Uniforme`,
    `CuidadosMantenimiento`,
    `QueSalioBienMejoraria`,
    `ValoracionRevisionFinalDocente`
) VALUES

-- ==========================================================
-- FICHA 1: COLORACIÓN COMPLETA - CLIENTE 1
-- ==========================================================
(
    'finalizada',
    '2026-11-02 09:00:00',
    '2026-11-02 12:15:00',
    '2026-11-02 09:00:00',
    1,
    1,
    NULL,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    NULL,
    'Coloración anterior castaño medio 5.0 realizada hace aproximadamente 3 meses. Sin decoloraciones previas.',
    0,
    NULL,
    'Sin alteraciones visibles.',
    'Media espalda',
    2.50,
    'Medio',
    35,
    'Canas concentradas principalmente en zona frontal y sienes.',
    'Altura 5, reflejo natural.',
    'Altura 5, reflejo cálido.',
    'Altura 5, reflejo cálido.',
    'Porosidad baja, resistencia normal.',
    'Porosidad media, resistencia normal.',
    'Porosidad media, puntas ligeramente sensibilizadas.',
    'Prueba realizada 48 horas antes. Sin reacción.',
    'Mechón de prueba correcto. Sin rotura ni alteraciones.',
    '5',
    'Natural',
    'Fondo de aclaración 5-6.',
    'Unificar el color y cubrir las canas manteniendo un resultado natural. Aplicar coloración permanente.',
    1,
    'Línea profesional de coloración permanente',
    'División en cuatro secciones. Protección de contorno y preparación de la mezcla.',
    'Aplicación inicial en raíces. Extensión a medios y puntas según tiempos indicados por el fabricante.',
    '2026-11-02 09:30:00',
    '2026-11-02 10:25:00',
    55,
    'Sin incidencias. Se respetaron los tiempos de exposición.',
    'Castaño natural altura 5.',
    1,
    TRUE,
    'Utilizar champú para cabello coloreado. Aplicar mascarilla hidratante una vez por semana y proteger del calor.',
    'Buena cobertura de canas y resultado uniforme. Mejoraría la distribución inicial del producto en la zona de las sienes.',
    'Resultado correcto. Buena aplicación y control de tiempos. Prestar más atención a la saturación de producto en zonas con mayor porcentaje de canas.'
),

-- ==========================================================
-- FICHA 2: MECHAS Y MATIZ - CLIENTE 2
-- ==========================================================
(
    'finalizada',
    '2026-11-04 10:00:00',
    '2026-11-04 13:30:00',
    '2026-11-04 10:00:00',
    2,
    2,
    NULL,
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    NULL,
    'Cabello teñido previamente en altura 6 cobriza. Mechas antiguas en medios y puntas.',
    0,
    NULL,
    'Cuero cabelludo aparentemente sano.',
    'Por los hombros',
    3.00,
    'Fino',
    10,
    'Canas dispersas, principalmente en zona frontal.',
    'Altura 6, reflejo cobrizo.',
    'Altura 7, reflejo dorado.',
    'Altura 8, reflejo amarillento.',
    'Porosidad baja, resistencia normal.',
    'Porosidad media, resistencia moderada.',
    'Porosidad alta, resistencia reducida.',
    'Prueba realizada 48 horas antes. Sin reacción.',
    'Mechón de prueba correcto. Aclaración progresiva sin rotura.',
    '8',
    'Beige frío',
    'Fondo de aclaración 8-9.',
    'Conseguir luminosidad mediante mechas finas y matizar para neutralizar los reflejos amarillentos.',
    1,
    'Línea profesional de decoloración y matización',
    'Protección del cuero cabelludo. División en secciones y preparación de papel de aluminio.',
    'Realización de mechas finas en velo y laterales. Control visual de aclaración. Lavado y aplicación de matiz.',
    '2026-11-04 10:30:00',
    '2026-11-04 11:35:00',
    65,
    'Algunas puntas alcanzaron un nivel de aclaración inferior al esperado. Se ajustó el matiz para mantener la armonía del conjunto.',
    'Rubio beige frío altura 8.',
    2,
    TRUE,
    'Utilizar champú y mascarilla específicos para cabello rubio. Aplicar protector térmico y evitar herramientas de calor excesivo.',
    'Buena distribución de las mechas y resultado natural. Mejoraría la selección de algunos mechones en la zona posterior.',
    'Técnica correctamente ejecutada. Buen control de la decoloración. Vigilar la resistencia de las puntas en futuros servicios.'
),

-- ==========================================================
-- FICHA 3: COLORACIÓN DE RAÍZ - CLIENTE 3
-- Estado borrador para probar el guardado progresivo
-- ==========================================================
(
    'borrador',
    '2026-11-08 16:00:00',
    NULL,
    '2026-11-10 10:00:00',
    3,
    3,
    NULL,
    FALSE,
    TRUE,
    FALSE,
    FALSE,
    NULL,
    'Coloración anterior castaño claro altura 6. Último servicio realizado hace 2 meses.',
    2,
    NULL,
    'Ligera sensibilidad en algunas zonas. Pendiente de valoración antes del tratamiento.',
    'Por los hombros',
    2.00,
    'Grueso',
    20,
    'Canas distribuidas de forma homogénea, con mayor presencia en zona frontal.',
    'Altura 6, reflejo natural.',
    'Altura 6, reflejo natural.',
    'Altura 6, reflejo natural.',
    'Porosidad baja, resistencia alta.',
    'Porosidad media, resistencia normal.',
    'Porosidad media, resistencia normal.',
    'Pendiente de realizar prueba de alergia.',
    'Pendiente de realizar prueba de mechón.',
    '6',
    'Natural',
    'Fondo 6.',
    'Retocar crecimiento de raíz y mantener la continuidad del color existente. Confirmar tolerancia y estado del cuero cabelludo antes de proceder.',
    NULL,
    'Línea profesional de coloración permanente',
    'Pendiente de valoración del cuero cabelludo y realización de pruebas previas.',
    'Pendiente de determinar tras las pruebas y aprobación docente.',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    FALSE,
    'Pendiente de definir tras el tratamiento.',
    'Ficha iniciada. Pendiente de completar pruebas y confirmar el procedimiento.',
    NULL
);


INSERT INTO `FORMULAS_TRATAMIENTO` (
    `FK_Ficha`,
    `ZonaFase`,
    `Tono`,
    `Gramos`,
    `Oxidante`,
    `CantidadOxidante`,
    `Proporcion`,
    `Tiempo`
) VALUES

-- ==========================================================
-- FICHA 1: COLORACIÓN COMPLETA
-- ==========================================================

-- Fórmula para raíces
(
    1,
    'Raiz',
    '5.0 Castaño natural',
    60.00,
    '20 volúmenes',
    60.00,
    '1:1',
    '35 minutos'
),

-- Fórmula para medios y puntas
(
    1,
    'Medios y puntas',
    '5.0 Castaño natural',
    40.00,
    '10 volúmenes',
    40.00,
    '1:1',
    '15 minutos'
),

-- ==========================================================
-- FICHA 2: MECHAS Y MATIZ
-- ==========================================================

-- Fórmula para mechas
(
    2,
    'Mechas',
    'Polvo decolorante azul',
    30.00,
    '30 volúmenes',
    60.00,
    '1:2',
    '45 minutos con control visual'
),

-- Fórmula para matización
(
    2,
    'Matiz',
    '9.1 Rubio muy claro ceniza',
    30.00,
    '10 volúmenes',
    60.00,
    '1:2',
    '15 minutos'
),

-- ==========================================================
-- FICHA 3: RETOQUE DE RAÍZ
-- Tratamiento todavía en borrador
-- ==========================================================

-- Fórmula prevista para raíces
(
    3,
    'Raiz',
    '6.0 Rubio oscuro natural',
    50.00,
    '20 volúmenes',
    50.00,
    '1:1',
    '35 minutos'
);
