# 🔐 Sistema de Verificación de Documentos PDF

## 📋 Descripción General

Sistema de verificación de autenticidad de documentos PDF generados por el sistema mediante hash blockchain-style (SHA-256) y códigos QR para prevenir falsificaciones y fraudes.

---

## 🎯 Problema a Resolver

### Escenarios Críticos:

**1. Auditoría Fiscal**
- Inspector necesita verificar legitimidad de reportes
- Sin el sistema: Imposible validar autenticidad
- Con el sistema: Verificación instantánea mediante QR

**2. Disputas Contables**
- Múltiples versiones de un mismo reporte
- Sin el sistema: No hay forma de saber cuál es legítimo
- Con el sistema: Hash único identifica el original

**3. Fraude Interno**
- Empleado manipula reportes para ocultar irregularidades
- Sin el sistema: Difícil de detectar
- Con el sistema: Hash no coincide → Fraude detectado

---

## 🏗️ Arquitectura del Sistema

### Flujo de Generación

```
┌──────────────────────────────────────────────────┐
│ 1. GENERACIÓN DE PDF                             │
├──────────────────────────────────────────────────┤
│ - Sistema genera PDF con datos del reporte       │
│ - Extrae contenido relevante para hash          │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 2. CÁLCULO DE HASH                               │
├──────────────────────────────────────────────────┤
│ - Calcula SHA-256 del contenido                  │
│ - Formato: a7f3c9e1... (64 caracteres hex)      │
│ - Inmutable: Cualquier cambio lo rompe           │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 3. REGISTRO EN BASE DE DATOS                     │
├──────────────────────────────────────────────────┤
│ - hash (único, indexed)                          │
│ - document_type (sale_report, inventory, etc)    │
│ - metadata (fecha, usuario, tenant)              │
│ - access_log (registro de verificaciones)        │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 4. GENERACIÓN DE QR CODE                         │
├──────────────────────────────────────────────────┤
│ - URL: https://app.com/verify/{hash}            │
│ - QR con corrección de errores nivel H          │
│ - Tamaño óptimo para escaneo                    │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 5. INSERCIÓN EN PDF                              │
├──────────────────────────────────────────────────┤
│ - Hash visible en el documento                   │
│ - QR code en esquina/footer                      │
│ - Texto: "Verificar autenticidad"                │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 6. ENTREGA AL USUARIO                            │
├──────────────────────────────────────────────────┤
│ - PDF listo con hash + QR integrados             │
│ - Usuario puede verificar en cualquier momento   │
└──────────────────────────────────────────────────┘
```

### Flujo de Verificación

```
┌──────────────────────────────────────────────────┐
│ 1. USUARIO ESCANEA QR o INGRESA HASH             │
├──────────────────────────────────────────────────┤
│ - Escaneo QR → Redirección automática            │
│ - Manual → Formulario público                    │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 2. BÚSQUEDA EN BASE DE DATOS                     │
├──────────────────────────────────────────────────┤
│ - Busca hash en document_verifications           │
│ - Registra intento de verificación               │
└──────────────────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────────────────┐
│ 3. RESULTADO DE VERIFICACIÓN                     │
├──────────────────────────────────────────────────┤
│ ✅ LEGÍTIMO:                                     │
│   - Fecha de generación                          │
│   - Tipo de documento                            │
│   - Estado: Válido                               │
│   - Metadata sanitizada (sin datos sensibles)    │
│                                                  │
│ ❌ INVÁLIDO:                                     │
│   - Hash no encontrado                           │
│   - Documento posiblemente falsificado           │
│   - Alerta de seguridad                          │
└──────────────────────────────────────────────────┘
```

---

## 💾 Estructura de Base de Datos

### Tabla: `document_verifications`

```sql
CREATE TABLE document_verifications (
    id BIGINT PRIMARY KEY,
    hash VARCHAR(64) UNIQUE NOT NULL,           -- SHA-256 hash
    document_type VARCHAR(50) NOT NULL,         -- 'sale_report', 'inventory_report', etc.
    tenant_id BIGINT NOT NULL,                  -- Tenant que generó el documento
    generated_by BIGINT NULL,                   -- Usuario que generó (puede ser null para sistema)
    generated_at TIMESTAMP NOT NULL,            -- Cuándo se generó
    metadata JSON NULL,                         -- Datos adicionales (sanitizados)
    verification_count INT DEFAULT 0,           -- Cuántas veces se verificó
    last_verified_at TIMESTAMP NULL,            -- Última verificación
    expires_at TIMESTAMP NULL,                  -- Opcional: expiración del documento
    is_valid BOOLEAN DEFAULT TRUE,              -- Puede invalidarse manualmente
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_hash (hash),
    INDEX idx_document_type (document_type),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_generated_at (generated_at)
);
```

### Tabla: `document_verification_logs`

```sql
CREATE TABLE document_verification_logs (
    id BIGINT PRIMARY KEY,
    verification_id BIGINT NOT NULL,            -- FK a document_verifications
    ip_address VARCHAR(45) NULL,                -- IP del verificador
    user_agent TEXT NULL,                       -- Browser info
    verified_at TIMESTAMP NOT NULL,             -- Cuándo se verificó
    result VARCHAR(20) NOT NULL,                -- 'valid', 'invalid', 'expired'
    created_at TIMESTAMP,
    
    FOREIGN KEY (verification_id) REFERENCES document_verifications(id),
    INDEX idx_verification_id (verification_id),
    INDEX idx_verified_at (verified_at)
);
```

---

## 🔧 Componentes Técnicos

### 1. Servicio de Hash

**Clase:** `App\Services\DocumentHashService`

```php
class DocumentHashService
{
    /**
     * Genera hash SHA-256 del contenido del documento
     */
    public function generateHash(array $content): string;
    
    /**
     * Verifica si un hash existe y es válido
     */
    public function verifyHash(string $hash): VerificationResult;
    
    /**
     * Registra verificación en log
     */
    public function logVerification(string $hash, string $result): void;
}
```

### 2. Servicio de QR

**Clase:** `App\Services\QRCodeService`

```php
class QRCodeService
{
    /**
     * Genera QR code para un hash
     */
    public function generateQR(string $hash): string;
    
    /**
     * Obtiene URL de verificación
     */
    public function getVerificationUrl(string $hash): string;
}
```

### 3. Trait para PDFs

**Trait:** `App\Traits\VerifiablePDF`

```php
trait VerifiablePDF
{
    /**
     * Agrega hash y QR al PDF
     */
    protected function addVerificationInfo(PDF $pdf, array $content): PDF;
    
    /**
     * Genera y registra hash del documento
     */
    protected function generateDocumentHash(array $content): string;
    
    /**
     * Agrega footer con QR y hash
     */
    protected function addVerificationFooter(PDF $pdf, string $hash, string $qr): void;
}
```

### 4. Modelo

**Modelo:** `App\Models\DocumentVerification`

```php
class DocumentVerification extends Model
{
    // Relaciones
    public function tenant(): BelongsTo;
    public function generatedBy(): BelongsTo;
    public function verificationLogs(): HasMany;
    
    // Scopes
    public function scopeValid($query);
    public function scopeByType($query, $type);
    
    // Métodos
    public function verify(): VerificationResult;
    public function invalidate(string $reason): void;
    public function getSanitizedMetadata(): array;
}
```

---

## 🌐 Página Pública de Verificación

### Ruta (SIN autenticación requerida)

```
GET /verify/{hash}
GET /verify (formulario manual)
```

### Vista: `resources/views/verify/index.blade.php`

**Características:**
- ✅ Acceso público (no requiere login)
- ✅ Diseño clean y profesional
- ✅ Formulario para ingresar hash manualmente
- ✅ Resultado visual claro (✅ válido / ❌ inválido)
- ✅ Metadata sanitizada (sin datos sensibles)
- ✅ Contador de verificaciones
- ✅ Responsive design

**Datos Mostrados (si válido):**
```
┌─────────────────────────────────────────┐
│ ✅ DOCUMENTO LEGÍTIMO                   │
├─────────────────────────────────────────┤
│ Hash: a7f3c9e1b2d4...                   │
│ Tipo: Reporte de Ventas                │
│ Generado: 15/01/2025 14:32             │
│ Empresa: [CENSURADO]                   │
│ Usuario: [CENSURADO]                   │
│ Estado: ✅ Válido                       │
│ Verificaciones: 3 veces                │
└─────────────────────────────────────────┘
```

**Datos NO Mostrados (seguridad):**
- ❌ Nombres de clientes
- ❌ Montos exactos
- ❌ Datos financieros específicos
- ❌ Información personal
- ❌ Detalles internos del tenant

---

## 🔐 Seguridad

### 1. Hash Inmutable
- Cualquier modificación del PDF rompe el hash
- Imposible de falsificar sin acceso a la BD

### 2. Timestamp Integrity
- Fecha de generación registrada
- Detecta intentos de backdating

### 3. Sanitización de Datos
- Metadata pública no expone información sensible
- Solo información necesaria para validar autenticidad

### 4. Rate Limiting
- Límite de verificaciones por IP
- Previene ataques de fuerza bruta

### 5. Audit Trail
- Registro de todas las verificaciones
- IP, user agent, timestamp

---

## 📊 Tipos de Documentos Verificables

### Iniciales (MVP):

1. **Reportes de Ventas**
   - `document_type: 'sale_report'`
   - Periodo, total general (sin detalles)

2. **Reportes de Inventario**
   - `document_type: 'inventory_report'`
   - Fecha, cantidad de productos

3. **Reportes de Devoluciones**
   - `document_type: 'return_report'`
   - Periodo, cantidad de devoluciones

### Futuro (Extensible):

4. Facturas individuales
5. Notas de crédito
6. Órdenes de compra
7. Reportes financieros
8. Reportes de auditoría

---

## 🎨 Diseño del QR en PDF

### Ubicación
- **Opción 1:** Footer derecho de cada página
- **Opción 2:** Última página, esquina inferior derecha
- **Opción 3:** Header derecho (marca de agua)

### Formato
```
┌────────────────────────────────────────┐
│                                        │
│  [Logo]  REPORTE DE VENTAS       [QR] │
│                                   ▪▪▪  │
│  Fecha: 15/01/2025                ▪▪▪  │
│  Hash: a7f3c9e1b2d4f8...          ▪▪▪  │
│                                   ▪▪▪  │
│  [Contenido del reporte...]            │
│                                        │
│  ────────────────────────────────────  │
│  Verificar autenticidad:               │
│  https://app.com/verify/a7f3c9e1...    │
└────────────────────────────────────────┘
```

---

## 🚀 Plan de Implementación

### Fase 1: Infraestructura ✅
1. Crear migraciones
2. Crear modelos
3. Crear servicios (Hash, QR)
4. Crear trait VerifiablePDF

### Fase 2: Página de Verificación ⏳
1. Crear ruta pública
2. Crear controlador
3. Crear vistas
4. Implementar sanitización

### Fase 3: Integración ⏳
1. Integrar en reporte de ventas
2. Integrar en reporte de inventario
3. Integrar en reporte de devoluciones

### Fase 4: Testing & Polish ⏳
1. Testing de generación
2. Testing de verificación
3. Testing de seguridad
4. Documentación para usuarios

---

## 📦 Dependencias

```json
{
  "require": {
    "simplesoftwareio/simple-qrcode": "^4.2",
    "spatie/laravel-pdf": "^1.0"
  }
}
```

---

## 💡 Valor Agregado

### Para el Negocio
- ✅ **Trust & Security:** Diferenciador enterprise
- ✅ **Compliance:** Facilita auditorías
- ✅ **Anti-fraude:** Prevención built-in
- ✅ **Competitividad:** Nadie más lo tiene
- ✅ **Premium Pricing:** Justifica precio superior

### Para el Usuario
- ✅ **Confianza:** Documentos verificables
- ✅ **Facilidad:** QR scan instantáneo
- ✅ **Transparencia:** Verificación pública
- ✅ **Seguridad:** Detección de falsificaciones

---

## 🎯 Métricas de Éxito

1. **Adopción:** % de reportes con verificación habilitada
2. **Uso:** Cantidad de verificaciones por mes
3. **Detección:** Intentos de verificación de hashes inválidos
4. **Confianza:** Feedback de auditorías/usuarios

---

**Estado:** 🚧 En Desarrollo
**Prioridad:** 🔥 Alta
**Impacto:** 🚀 Game Changer
