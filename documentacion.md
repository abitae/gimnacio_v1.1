# Modelos del Sistema
## Administracion de Gimnasio
**Laravel 12 · Backend API · DNI / CE · Flutter**

---

## Descripcion general

Este documento define los modelos del backend para el Sistema de Administracion de un Gimnasio, basados en los requerimientos funcionales aprobados.

El sistema considera:
- Identificacion unica de clientes por DNI o Carnet de Extranjeria.
- Arquitectura de 3 proyectos: Backend API, Frontend Web y App Flutter.
- Escalabilidad, auditoria y control de accesos.

---

## Modelos principales

### 1. User
Representa a los usuarios del sistema, como administradores, recepcionistas, entrenadores y personal contable.

Campos relevantes:
- id
- name
- email
- password
- estado
- timestamps

Relaciones:
- hasMany(Pago)
- hasMany(Asistencia)
- hasMany(EvaluacionFisica)
- hasMany(AuditLog)

### 2. Cliente
Entidad central del sistema. Representa a una persona inscrita en el gimnasio.

Identificacion oficial unica:
- tipo_documento (DNI | CE)
- numero_documento

Campos relevantes:
- tipo_documento
- numero_documento
- nombres
- apellidos
- telefono
- email
- direccion
- estado_cliente
- foto
- datos_salud
- datos_emergencia
- consentimientos

Relaciones:
- hasMany(ClienteMembresia)
- hasMany(Pago)
- hasMany(Asistencia)
- hasMany(EvaluacionFisica)

### 3. Membresia
Catalogo de planes del gimnasio.

Campos relevantes:
- nombre
- descripcion
- duracion_dias
- precio_base
- tipo_acceso
- max_visitas_dia
- permite_congelacion
- max_dias_congelacion
- estado

Relaciones:
- hasMany(ClienteMembresia)

### 4. ClienteMembresia
Historial de membresias adquiridas por un cliente.

Campos relevantes:
- cliente_id
- membresia_id
- fecha_inicio
- fecha_fin
- estado
- precio_lista
- descuento_monto
- precio_final
- asesor_id
- canal_venta
- fechas_congelacion
- motivo_cancelacion

Relaciones:
- belongsTo(Cliente)
- belongsTo(Membresia)
- belongsTo(User) como asesor
- hasMany(Pago)
- hasMany(Asistencia)

### 5. Pago
Registro de pagos realizados por los clientes.

Campos relevantes:
- cliente_id
- cliente_membresia_id
- monto
- moneda
- metodo_pago
- fecha_pago
- es_pago_parcial
- saldo_pendiente
- comprobante_tipo
- comprobante_numero
- registrado_por

Relaciones:
- belongsTo(Cliente)
- belongsTo(ClienteMembresia)
- belongsTo(User)

### 6. Asistencia
Registro de ingresos del cliente al gimnasio.

Origen del acceso:
- manual
- app

Campos relevantes:
- cliente_id
- cliente_membresia_id
- fecha_hora_ingreso
- fecha_hora_salida
- origen
- valido_por_membresia
- registrada_por

Relaciones:
- belongsTo(Cliente)
- belongsTo(ClienteMembresia)
- belongsTo(User)

### 7. EvaluacionFisica
Historial de evaluaciones corporales del cliente.

Campos relevantes:
- cliente_id
- peso
- estatura
- imc
- porcentaje_grasa
- porcentaje_musculo
- perimetros_corporales
- presion_arterial
- frecuencia_cardiaca
- observaciones
- evaluado_por

Relaciones:
- belongsTo(Cliente)
- belongsTo(User)

---

## Modelos de integracion, configuracion y auditoria

### IntegrationErrorLog
Registro de errores en integraciones externas.

Campos relevantes:
- source (api, webhook)
- payload
- error_message
- resolved_at

### GymSetting
Configuracion general del gimnasio.

Campos relevantes:
- nombre_gimnasio
- ruc
- direccion
- telefono
- email
- logo
- horarios_acceso
- politicas_acceso

### AuditLog
Registro de acciones criticas del sistema.

Campos relevantes:
- user_id
- action
- entity_type
- entity_id
- payload_before
- payload_after
- ip
- user_agent

Relaciones:
- belongsTo(User)

---

## Resumen

Modelos esenciales:
- User
- Cliente
- Membresia
- ClienteMembresia
- Pago
- Asistencia
- EvaluacionFisica

Modelos de soporte:
- IntegrationErrorLog
- GymSetting
- AuditLog

Este archivo sirve como base para crear migraciones, definir relaciones Eloquent, documentar el backend y coordinar los proyectos web y movil.
