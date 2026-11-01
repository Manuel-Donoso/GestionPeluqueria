USE peluqueria;

-- 1. Limpiar las tablas usando DELETE (evita el error 1701)
DELETE FROM AUDITORIA;
DELETE FROM AGENDA;
DELETE FROM FACTURA;
DELETE FROM HISTORIALCLIENTES;
DELETE FROM SERVICIOSPRECIOS;
DELETE FROM SERVICIOS;
DELETE FROM CLIENTES;
DELETE FROM PELUQUEROS;

-- 2. Reiniciar los contadores AUTO_INCREMENT (opcional, para que los IDs empiecen desde 1)
ALTER TABLE AUDITORIA AUTO_INCREMENT = 1;
ALTER TABLE AGENDA AUTO_INCREMENT = 1;
ALTER TABLE FACTURA AUTO_INCREMENT = 1;
ALTER TABLE HISTORIALCLIENTES AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOSPRECIOS AUTO_INCREMENT = 1;
ALTER TABLE SERVICIOS AUTO_INCREMENT = 1;
ALTER TABLE CLIENTES AUTO_INCREMENT = 1;
ALTER TABLE PELUQUEROS AUTO_INCREMENT = 1;

-- 3. Volver a insertar los datos de prueba

-- ---------------------------------------------------------------------
-- 1. PELUQUEROS
-- Contraseñas en texto plano para pruebas:
-- - Admin: "Admin2026*" -> Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi (Password por defecto estándar)
-- - Peluqueros: "Peluquero2026*" -> Hash: $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm
-- ---------------------------------------------------------------------
INSERT INTO PELUQUEROS ( Nombre, Apellido1, Apellido2, Telefono, Direccion, Email, Notas, PW, Restarurar, FK_rol, FechaAlta, FechaBaja, baja) VALUES
('Carlos', 'Administrador', 'Pérez', '600111222', 'Calle Mayor 1, Madrid', 'admin@peluqueria.com', 'Administrador principal del sistema.', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, '2026-01-01 08:00:00', NULL, 0),
('Laura', 'García', 'Sanz', '611222333', 'Avenida de América 45, Madrid', 'laura@peluqueria.com', 'Especialista en coloración y mechas.', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 0, 2, '2026-01-10 09:00:00', NULL, 0),
('Miguel', 'Fernández', 'López', '622333444', 'Calle Toledo 12, Madrid', 'miguel@peluqueria.com', 'Especialista en cortes modernos y barbería.', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 0, 2, '2026-02-01 09:00:00', NULL, 0);

-- ---------------------------------------------------------------------
-- 2. CLIENTES
-- ---------------------------------------------------------------------

INSERT INTO CLIENTES ( Nombre, Apellido1, Apellido2, Telefono, Direccion, Email, Notas, FechaAlta, FechaBaja, baja) VALUES
('Ana', 'Martínez', 'Ruiz', '633444555', 'Calle Goya 8, Madrid', 'ana.martinez@email.com', 'Prefiere productos sin amoníaco.', '2026-02-10 10:30:00', NULL, 0),
('David', 'Rodríguez', 'Gómez', '644555666', 'Calle Serrano 22, Madrid', 'david.rg@email.com', 'Corte clásico cada 3 semanas.', '2026-02-12 11:15:00', NULL, 0),
('Sofia', 'Alonso', 'Blanco', '655666777', 'Paseo de la Castellana 80, Madrid', 'sofia.alonso@email.com', 'Alérgica a ciertos tintes temporales.', '2026-02-15 16:00:00', NULL, 0),
('Javier', 'Jiménez', 'Pascual', '666777888', 'Calle Alcalá 150, Madrid', 'javier.jp@email.com', 'Cliente habitual de los sábados.', '2026-02-20 12:00:00', NULL, 0);

-- ---------------------------------------------------------------------
-- 3. SERVICIOS Y SERVICIOSPRECIOS
-- ---------------------------------------------------------------------

INSERT INTO SERVICIOS (servicio) VALUES
('Corte de Cabello'),
('Tinte / Coloración'),
('Mechas Balayage'),
('Lavado y Peinado'),
('Arreglo de Barba');



INSERT INTO SERVICIOSPRECIOS ( FK_Servicio, precio) VALUES
( 1, 15.00),
( 2, 35.00),
( 3, 55.00),
( 4, 20.00),
( 5, 12.00);

-- ---------------------------------------------------------------------
-- 4. HISTORIAL DE CLIENTES
-- ---------------------------------------------------------------------

INSERT INTO HISTORIALCLIENTES ( observaciones, FK_Cliente, FK_peluquero) VALUES
('Se realizó corte degradado y marcaje de barba sin incidencias.', 2, 3),
( 'Aplicación de tinte castaño claro y peinado ondas suaves.', 1, 2);

-- ---------------------------------------------------------------------
-- 5. FACTURAS (Precio congelado en el momento de la venta)
-- ---------------------------------------------------------------------
INSERT INTO FACTURA ( Fecha, FK_Cliente, FK_peluquero, Servicio, Precio, notas) VALUES
( '2026-02-10 11:30:00', 1, 2, 'Tinte / Coloración', 35.00, 'Pago con tarjeta.'),
( '2026-02-12 12:00:00', 2, 3, 'Corte de Cabello', 15.00, 'Pago en efectivo.'),
( '2026-02-15 17:15:00', 3, 2, 'Lavado y Peinado', 20.00, 'Cliente muy satisfecho.');

-- ---------------------------------------------------------------------
-- 6. AGENDA (Citas programadas)
-- ---------------------------------------------------------------------
INSERT INTO AGENDA ( Fecha, Hora, FK_Cliente, FK_peluquero) VALUES
( '2026-11-02', '10:00:00', 1, 2),
( '2026-11-02', '11:00:00', 2, 3),
( '2026-11-03', '16:30:00', 3, 2);