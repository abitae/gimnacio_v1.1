# 📦 MODELOS DEL SISTEMA  
## Administración de Gimnasio  
**Laravel 12 · Backend API · DNI / CE · Flutter · ZKTeco / BioTime**

---

## 📌 Descripción general

Este documento define **exclusivamente los modelos del backend** necesarios para el Sistema de Administración de un Gimnasio, basados en los requerimientos funcionales aprobados.

El sistema considera:
- Identificación única de clientes por **DNI o Carnet de Extranjería**
- Arquitectura de **3 proyectos** (Backend API, Frontend Web, App Flutter)
- Preparación para integración con **ZKTeco / BioTime**
- Escalabilidad, auditoría y control de accesos

---

## 🧠 MODELOS PRINCIPALES (CORE)

---

### 1️⃣ User
**Descripción:**  
Representa a los usuarios del sistema (staff del gimnasio).

**Uso principal:**
- Autenticación
- Autorización por roles
- Registro de acciones (auditoría)

**Campos relevantes:**
- id
- name
- email
- password
- role (admin, recepcion, entrenador, contabilidad)
- estado
- timestamps

**Relaciones:**
- hasMany(Pago)
- hasMany(Asistencia)
- hasMany(EvaluacionFisica)
- hasMany(AuditLog)

---

### 2️⃣ Cliente
**Descripción:**  
Entidad central del sistema. Representa a una persona inscrita en el gimnasio.

**Identificación oficial única:**
- tipo_documento (DNI | CE)
- numero_documento

**Campos relevantes:**
- tipo_documento
- numero_documento (único)
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
- biotime_user_id (nullable)

**Relaciones:**
- hasMany(ClienteMembresia)
- hasMany(Pago)
- hasMany(Asistencia)
- hasMany(EvaluacionFisica)
- hasMany(BiotimeAccessLog)

---

### 3️⃣ Membresia
**Descripción:**  
Catálogo de planes del gimnasio.

**Campos relevantes:**
- nombre
- descripcion
- duracion_dias
- precio_base
- tipo_acceso
- max_visitas_dia
- permite_congelacion
- max_dias_congelacion
- estado

**Relaciones:**
- hasMany(ClienteMembresia)

---

### 4️⃣ ClienteMembresia
**Descripción:**  
Historial de membresías adquiridas por un cliente.

**Campos relevantes:**
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

**Relaciones:**
- belongsTo(Cliente)
- belongsTo(Membresia)
- belongsTo(User) → asesor
- hasMany(Pago)
- hasMany(Asistencia)

---

### 5️⃣ Pago
**Descripción:**  
Registro de pagos realizados por los clientes.

**Campos relevantes:**
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

**Relaciones:**
- belongsTo(Cliente)
- belongsTo(ClienteMembresia)
- belongsTo(User)

---

### 6️⃣ Asistencia
**Descripción:**  
Registro de ingresos del cliente al gimnasio.

**Origen del acceso:**
- manual (web)
- app (Flutter)
- biotime (ZKTeco)

**Campos relevantes:**
- cliente_id
- cliente_membresia_id
- fecha_hora_ingreso
- fecha_hora_salida
- origen
- valido_por_membresia
- registrada_por

**Relaciones:**
- belongsTo(Cliente)
- belongsTo(ClienteMembresia)
- belongsTo(User)

---

### 7️⃣ EvaluacionFisica
**Descripción:**  
Historial de evaluaciones corporales del cliente.

**Campos relevantes:**
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

**Relaciones:**
- belongsTo(Cliente)
- belongsTo(User)

---

## 🔗 MODELOS PARA INTEGRACIÓN ZKTECO / BIOTIME

---

### 8️⃣ BiotimeAccessLog
**Descripción:**  
Almacena los eventos de acceso biométrico recibidos desde BioTime.

**Campos relevantes:**
- biotime_user_id
- cliente_id (nullable)
- device_id
- event_time
- event_type (entry / exit)
- result (success / denied)
- raw_payload

**Relaciones:**
- belongsTo(Cliente)

---

### 9️⃣ IntegrationErrorLog
**Descripción:**  
Registro de errores en integraciones externas.

**Campos relevantes:**
- source (biotime, api, webhook)
- payload
- error_message
- resolved_at

---

## ⚙️ MODELOS DE CONFIGURACIÓN Y AUDITORÍA

---

### 🔟 GymSetting
**Descripción:**  
Configuración general del gimnasio.

**Campos relevantes:**
- nombre_gimnasio
- ruc
- direccion
- telefono
- email
- logo
- horarios_acceso
- politicas_acceso

---

### 1️⃣1️⃣ AuditLog
**Descripción:**  
Registro de acciones críticas del sistema.

**Campos relevantes:**
- user_id
- action
- entity_type
- entity_id
- payload_before
- payload_after
- ip
- user_agent

**Relaciones:**
- belongsTo(User)

---

## ✅ RESUMEN DE MODELOS

### 🔹 Modelos esenciales:
- User
- Cliente
- Membresia
- ClienteMembresia
- Pago
- Asistencia
- EvaluacionFisica

### 🔹 Modelos avanzados / integración:
- BiotimeAccessLog
- IntegrationErrorLog
- GymSetting
- AuditLog

---

📌 **Este archivo sirve como base directa para:**
- Crear migraciones en Laravel 12
- Definir relaciones Eloquent
- Documentar el backend
- Coordinar Frontend Web, App Flutter y BioTime
