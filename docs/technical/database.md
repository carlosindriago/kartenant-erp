# 🗄️ Base de Datos - Kartenant

Documentación completa del esquema de base de datos, migraciones y diseño de datos de Kartenant.

## 📋 Visión General

Kartenant utiliza una arquitectura **multi-tenant con database-per-tenant**, implementada con PostgreSQL y Spatie Laravel Multitenancy.

### 🏗️ Arquitectura de Base de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE ARCHITECTURE                    │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   LANDLORD DB   │  │   TENANT DBS    │  │   SHARED     │ │
│  │   (Sistema)     │  │   (Por empresa) │  │   (Redis)    │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│  │ • domains       │  │ • tenant_001    │  │ • Sessions   │ │
│  │ • tenants       │  │ • tenant_002    │  │ • Cache      │ │
│  │ • users         │  │ • tenant_003    │  │ • Queues     │ │
│  │ • subscriptions │  │   ...           │  │ • Locks      │ │
│  │ • backups       │  │                 │  │             │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏢 Base de Datos Landlord (Sistema)

### Tabla: `tenants`

```sql
CREATE TABLE tenants (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255),
    country VARCHAR(2),
    timezone VARCHAR(50) DEFAULT 'America/Argentina/Buenos_Aires',
    settings JSONB DEFAULT '{}',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_domain (domain),
    INDEX idx_active (is_active)
);
```

### Tabla: `domains`

```sql
CREATE TABLE domains (
    id BIGSERIAL PRIMARY KEY,
    domain VARCHAR(255) UNIQUE NOT NULL,
    tenant_id BIGINT REFERENCES tenants(id) ON DELETE CASCADE,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id),
    INDEX idx_domain (domain)
);
```

### Tabla: `users` (Superadministradores)

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email)
);
```

### Tabla: `subscriptions`

```sql
CREATE TABLE subscriptions (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL REFERENCES tenants(id),
    plan_name VARCHAR(50) NOT NULL, -- 'basic', 'professional', 'enterprise'
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'past_due', 'cancelled'
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    trial_ends_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_period_end (current_period_end)
);
```

---

## 🏪 Bases de Datos Tenant (Por Empresa)

### Tabla: `users` (Usuarios del tenant)

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL, -- Para queries cross-tenant
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,

    -- 2FA y seguridad
    email_2fa_code VARCHAR(6) NULL,
    email_2fa_expires_at TIMESTAMP NULL,
    password_change_code VARCHAR(6) NULL,
    password_change_code_expires_at TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,

    -- Control de acceso
    must_change_password BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_email_per_tenant (tenant_id, email),
    INDEX idx_tenant_active (tenant_id, is_active),
    INDEX idx_last_login (last_login_at)
);
```

### Tabla: `categories`

```sql
CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    color VARCHAR(7) NULL, -- Hex color
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_active (tenant_id, is_active),
    INDEX idx_sort_order (tenant_id, sort_order)
);
```

### Tabla: `taxes`

```sql
CREATE TABLE taxes (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(100) NOT NULL, -- "IVA 21%", "IVA 10.5%"
    rate DECIMAL(5,2) NOT NULL, -- 21.00, 10.50
    type VARCHAR(20) DEFAULT 'percentage', -- 'percentage', 'fixed'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_active (tenant_id, is_active)
);
```

### Tabla: `products`

```sql
CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,
    barcode VARCHAR(100) NULL,
    description TEXT NULL,

    -- Precios
    price DECIMAL(12,2) NOT NULL, -- Precio base (sin IVA)

    -- Relaciones
    category_id BIGINT NULL REFERENCES categories(id) ON DELETE SET NULL,
    tax_id BIGINT NULL REFERENCES taxes(id) ON DELETE SET NULL,

    -- Inventario
    stock DECIMAL(10,2) DEFAULT 0,
    min_stock DECIMAL(10,2) DEFAULT 0,
    max_stock DECIMAL(10,2) NULL,

    -- Control
    is_active BOOLEAN DEFAULT TRUE,
    track_stock BOOLEAN DEFAULT TRUE,
    allow_negative_stock BOOLEAN DEFAULT FALSE,

    -- Metadata
    weight DECIMAL(8,3) NULL,
    dimensions JSONB NULL, -- {"width": 10, "height": 5, "depth": 2}

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_tenant_active (tenant_id, is_active),
    INDEX idx_category (category_id),
    INDEX idx_sku (sku),
    INDEX idx_barcode (barcode),
    INDEX idx_tenant_deleted (tenant_id, deleted_at)
);
```

### Tabla: `customers`

```sql
CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    document_type VARCHAR(10) NULL, -- 'DNI', 'CUIT', 'RUC'
    document_number VARCHAR(50) NULL,

    -- Dirección
    address_street VARCHAR(255) NULL,
    address_number VARCHAR(50) NULL,
    address_city VARCHAR(100) NULL,
    address_state VARCHAR(100) NULL,
    address_country VARCHAR(2) DEFAULT 'AR',
    address_postal_code VARCHAR(20) NULL,

    -- Crédito
    credit_limit DECIMAL(12,2) DEFAULT 0,
    current_balance DECIMAL(12,2) DEFAULT 0,

    -- Control
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_tenant_active (tenant_id, is_active),
    INDEX idx_email (email),
    INDEX idx_document (document_type, document_number),
    INDEX idx_tenant_deleted (tenant_id, deleted_at)
);
```

### Tabla: `sales`

```sql
CREATE TABLE sales (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,

    -- Relaciones
    customer_id BIGINT NULL REFERENCES customers(id) ON DELETE SET NULL,
    user_id BIGINT NOT NULL, -- Cajero que realizó la venta

    -- Números y fechas
    invoice_number VARCHAR(50) UNIQUE NOT NULL, -- FAC-20251011-0001
    invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Totales
    subtotal DECIMAL(12,2) NOT NULL, -- Neto gravado (sin IVA)
    tax_amount DECIMAL(12,2) NOT NULL, -- Total IVA
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL, -- Total final

    -- Pago
    payment_method VARCHAR(20) NOT NULL, -- 'cash', 'card', 'transfer'
    payment_reference VARCHAR(100) NULL,
    amount_paid DECIMAL(12,2) NULL,
    change_amount DECIMAL(12,2) DEFAULT 0,

    -- Estado
    status VARCHAR(20) DEFAULT 'completed', -- 'pending', 'completed', 'cancelled'
    notes TEXT NULL,

    -- Verificación
    verification_hash VARCHAR(64) NULL,
    verified_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_date (tenant_id, invoice_date),
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_invoice_number (invoice_number)
);
```

### Tabla: `sale_items`

```sql
CREATE TABLE sale_items (
    id BIGSERIAL PRIMARY KEY,
    sale_id BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,

    -- Cantidad y precios
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL, -- Precio base en el momento de venta
    tax_rate DECIMAL(5,2) NOT NULL, -- Tasa de IVA aplicada
    line_total DECIMAL(12,2) NOT NULL, -- Subtotal de línea (con IVA)

    -- Información del producto al momento de venta
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sale (sale_id),
    INDEX idx_product (product_id)
);
```

### Tabla: `stock_movements`

```sql
CREATE TABLE stock_movements (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,

    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    user_id BIGINT NOT NULL, -- Usuario que realizó el movimiento

    -- Tipo y cantidad
    type VARCHAR(20) NOT NULL, -- 'entry', 'exit', 'adjustment'
    reason VARCHAR(50) NOT NULL, -- 'sale', 'purchase', 'return', 'adjustment'
    quantity DECIMAL(10,2) NOT NULL, -- Positivo = entrada, negativo = salida
    previous_stock DECIMAL(10,2) NOT NULL,
    new_stock DECIMAL(10,2) NOT NULL,

    -- Información adicional
    reference VARCHAR(100) NULL, -- Número de factura, orden de compra, etc.
    supplier_id BIGINT NULL REFERENCES suppliers(id),
    batch_number VARCHAR(100) NULL,
    expiry_date DATE NULL,
    unit_cost DECIMAL(12,2) NULL,

    -- Documento verificable
    document_number VARCHAR(50) NULL, -- ENT-20251011-0001, SAL-20251011-0001
    verification_hash VARCHAR(64) NULL,
    pdf_format VARCHAR(10) DEFAULT 'thermal', -- 'thermal', 'a4'

    -- Autorización (para salidas grandes)
    requires_authorization BOOLEAN DEFAULT FALSE,
    authorized_by BIGINT NULL REFERENCES users(id),
    authorized_at TIMESTAMP NULL,

    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_product (tenant_id, product_id),
    INDEX idx_type_reason (type, reason),
    INDEX idx_created_at (created_at),
    INDEX idx_document_number (document_number)
);
```

### Tabla: `suppliers`

```sql
CREATE TABLE suppliers (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    document_type VARCHAR(10) NULL,
    document_number VARCHAR(50) NULL,

    -- Dirección
    address_street VARCHAR(255) NULL,
    address_number VARCHAR(50) NULL,
    address_city VARCHAR(100) NULL,
    address_state VARCHAR(100) NULL,
    address_country VARCHAR(2) DEFAULT 'AR',
    address_postal_code VARCHAR(20) NULL,

    -- Información comercial
    payment_terms VARCHAR(100) NULL, -- "30 días", "contado"
    tax_condition VARCHAR(50) NULL, -- "Responsable Inscripto", "Monotributo"

    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_tenant_active (tenant_id, is_active),
    INDEX idx_name (name),
    INDEX idx_tenant_deleted (tenant_id, deleted_at)
);
```

### Tabla: `cash_register_openings`

```sql
CREATE TABLE cash_register_openings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL REFERENCES users(id),

    opening_number VARCHAR(50) UNIQUE NOT NULL, -- APR-20251011-0001
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    opening_balance DECIMAL(12,2) NOT NULL,

    status VARCHAR(20) DEFAULT 'open', -- 'open', 'closed'
    closed_at TIMESTAMP NULL,
    closed_by BIGINT NULL REFERENCES users(id),

    notes TEXT NULL,

    -- Verificación
    verification_hash VARCHAR(64) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_user (tenant_id, user_id),
    INDEX idx_status (status),
    INDEX idx_opened_at (opened_at),
    INDEX idx_opening_number (opening_number)
);
```

### Tabla: `cash_register_closings`

```sql
CREATE TABLE cash_register_closings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,

    opening_id BIGINT NOT NULL REFERENCES cash_register_openings(id),
    user_id BIGINT NOT NULL REFERENCES users(id),

    closing_number VARCHAR(50) UNIQUE NOT NULL, -- CIE-20251011-0001
    closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Saldos
    opening_balance DECIMAL(12,2) NOT NULL,
    total_sales DECIMAL(12,2) DEFAULT 0,
    total_cash_sales DECIMAL(12,2) DEFAULT 0,
    total_card_sales DECIMAL(12,2) DEFAULT 0,
    total_other_sales DECIMAL(12,2) DEFAULT 0,

    expected_balance DECIMAL(12,2) NOT NULL,
    closing_balance DECIMAL(12,2) NOT NULL,
    difference DECIMAL(12,2) NOT NULL, -- Positivo = sobrante, negativo = faltante

    -- Análisis
    total_transactions INTEGER DEFAULT 0,
    average_ticket DECIMAL(12,2) DEFAULT 0,

    status VARCHAR(20) DEFAULT 'pending_review', -- 'pending_review', 'approved', 'rejected'
    reviewed_by BIGINT NULL REFERENCES users(id),
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,

    discrepancy_reason VARCHAR(100) NULL,
    discrepancy_notes TEXT NULL,

    -- Verificación
    verification_hash VARCHAR(64) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_opening (tenant_id, opening_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_closed_at (closed_at),
    INDEX idx_closing_number (closing_number)
);
```

### Tabla: `sale_returns`

```sql
CREATE TABLE sale_returns (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,

    original_sale_id BIGINT NOT NULL REFERENCES sales(id) ON DELETE RESTRICT,
    user_id BIGINT NOT NULL REFERENCES users(id),

    return_number VARCHAR(50) UNIQUE NOT NULL, -- NCR-20251011-0001
    return_type VARCHAR(20) DEFAULT 'full', -- 'full', 'partial'
    status VARCHAR(20) DEFAULT 'completed', -- 'pending_approval', 'approved', 'rejected', 'completed'

    -- Motivos
    reason TEXT NULL,
    refund_method VARCHAR(20) DEFAULT 'cash', -- 'cash', 'credit_note', 'card'

    -- Totales
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,

    -- Autorización (si caja cerrada)
    requires_authorization BOOLEAN DEFAULT FALSE,
    cash_register_status VARCHAR(20) NULL, -- 'open', 'closed'
    authorized_by_user_id BIGINT NULL REFERENCES users(id),
    authorized_at TIMESTAMP NULL,
    authorization_notes TEXT NULL,

    rejected_by_user_id BIGINT NULL REFERENCES users(id),
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    -- Verificación
    verification_hash VARCHAR(64) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_sale (tenant_id, original_sale_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_return_type (return_type),
    INDEX idx_created_at (created_at),
    INDEX idx_return_number (return_number)
);
```

### Tabla: `sale_return_items`

```sql
CREATE TABLE sale_return_items (
    id BIGSERIAL PRIMARY KEY,
    sale_return_id BIGINT NOT NULL REFERENCES sale_returns(id) ON DELETE CASCADE,

    original_sale_item_id BIGINT NULL REFERENCES sale_items(id),
    product_id BIGINT NOT NULL REFERENCES products(id),

    product_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,

    return_reason TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sale_return (sale_return_id),
    INDEX idx_product (product_id)
);
```

### Tabla: `customer_credits`

```sql
CREATE TABLE customer_credits (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,

    customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    sale_return_id BIGINT NOT NULL REFERENCES sale_returns(id),

    original_amount DECIMAL(12,2) NOT NULL,
    used_amount DECIMAL(12,2) DEFAULT 0,
    remaining_amount DECIMAL(12,2) NOT NULL,

    status VARCHAR(20) DEFAULT 'active', -- 'active', 'fully_used', 'expired'
    expires_at DATE NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);
```

### Tabla: `customer_credit_applications`

```sql
CREATE TABLE customer_credit_applications (
    id BIGSERIAL PRIMARY KEY,

    customer_credit_id BIGINT NOT NULL REFERENCES customer_credits(id) ON DELETE CASCADE,
    sale_id BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,

    amount_applied DECIMAL(12,2) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    applied_by_user_id BIGINT NOT NULL REFERENCES users(id),

    INDEX idx_customer_credit (customer_credit_id),
    INDEX idx_sale (sale_id),
    INDEX idx_applied_by (applied_by_user_id)
);
```

---

## 🔄 Migraciones y Versionado

### Estructura de Migraciones

```
database/
├── migrations/
│   ├── landlord/          # Migraciones del sistema
│   │   ├── 2025_08_01_000001_create_tenants_table.php
│   │   ├── 2025_08_01_000002_create_domains_table.php
│   │   └── ...
│   └── tenant/            # Migraciones por tenant
│       ├── 2025_08_18_173600_create_categories_table.php
│       ├── 2025_08_18_173650_create_taxes_table.php
│       ├── 2025_08_18_173700_create_products_table.php
│       └── ...
```

### Orden de Migraciones Crítico

**Landlord (sistema):**
1. `tenants` - Base de tenants
2. `domains` - Dominios por tenant
3. `users` - Superadministradores
4. `subscriptions` - Planes de suscripción

**Tenant (por empresa):**
1. `categories` - Antes que products (FK)
2. `taxes` - Antes que products (FK)
3. `products` - Depende de categories y taxes
4. `customers` - Independiente
5. `suppliers` - Independiente
6. `users` - Usuarios del tenant
7. `cash_register_openings` - Independiente
8. `sales` - Depende de customers, users
9. `sale_items` - Depende de sales, products
10. `stock_movements` - Depende de products, users, suppliers
11. `cash_register_closings` - Depende de cash_register_openings, users
12. `sale_returns` - Depende de sales, users
13. `sale_return_items` - Depende de sale_returns, products
14. `customer_credits` - Depende de customers, sale_returns
15. `customer_credit_applications` - Depende de customer_credits, sales

---

## 📊 Índices y Performance

### Índices Estratégicos

```sql
-- Consultas por tenant (más comunes)
CREATE INDEX idx_products_tenant_active ON products (tenant_id, is_active);
CREATE INDEX idx_sales_tenant_date ON sales (tenant_id, invoice_date DESC);
CREATE INDEX idx_customers_tenant_active ON customers (tenant_id, is_active);

-- Búsquedas frecuentes
CREATE INDEX idx_products_sku ON products (sku) WHERE sku IS NOT NULL;
CREATE INDEX idx_products_barcode ON products (barcode) WHERE barcode IS NOT NULL;
CREATE INDEX idx_sales_invoice_number ON sales (invoice_number);

-- Reportes
CREATE INDEX idx_stock_movements_tenant_product ON stock_movements (tenant_id, product_id, created_at DESC);
CREATE INDEX idx_sale_items_product ON sale_items (product_id, created_at DESC);

-- Auditoría
CREATE INDEX idx_cash_register_closings_tenant_status ON cash_register_closings (tenant_id, status, closed_at DESC);
```

### Partitioning (Futuro)

```sql
-- Particionar sales por mes (para tenants grandes)
CREATE TABLE sales_y2025m10 PARTITION OF sales
FOR VALUES FROM ('2025-10-01') TO ('2025-11-01');

-- Particionar stock_movements por trimestre
CREATE TABLE stock_movements_q4_2025 PARTITION OF stock_movements
FOR VALUES FROM ('2025-10-01') TO ('2026-01-01');
```

---

## 🔒 Restricciones y Reglas de Integridad

### Foreign Keys

```sql
-- Products
ALTER TABLE products ADD CONSTRAINT fk_products_category
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

ALTER TABLE products ADD CONSTRAINT fk_products_tax
FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL;

-- Sales
ALTER TABLE sales ADD CONSTRAINT fk_sales_customer
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

ALTER TABLE sales ADD CONSTRAINT fk_sales_user
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

-- Sale Items
ALTER TABLE sale_items ADD CONSTRAINT fk_sale_items_sale
FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE;

ALTER TABLE sale_items ADD CONSTRAINT fk_sale_items_product
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;
```

### Check Constraints

```sql
-- Validar porcentajes
ALTER TABLE taxes ADD CONSTRAINT chk_tax_rate
CHECK (rate >= 0 AND rate <= 100);

-- Validar estados
ALTER TABLE sales ADD CONSTRAINT chk_sale_status
CHECK (status IN ('pending', 'completed', 'cancelled'));

-- Validar métodos de pago
ALTER TABLE sales ADD CONSTRAINT chk_payment_method
CHECK (payment_method IN ('cash', 'card', 'transfer'));

-- Validar stock no negativo (opcional)
ALTER TABLE products ADD CONSTRAINT chk_stock_positive
CHECK (stock >= 0 OR allow_negative_stock = true);
```

### Triggers (Auditoría)

```sql
-- Trigger para actualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_products_updated_at
BEFORE UPDATE ON products
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
```

---

## 🔄 Sincronización y Replicación

### Read Replicas (Futuro)

```sql
-- Configuración de réplicas de lectura
ALTER SYSTEM SET wal_level = replica;
ALTER SYSTEM SET max_wal_senders = 3;
ALTER SYSTEM SET hot_standby = on;
```

### Backup Strategy

```bash
# Backup completo semanal
pg_dump -U kartenant_user -h localhost kartenant > backup_weekly.sql

# Backup incremental diario
pg_dump -U kartenant_user -h localhost --data-only --inserts kartenant > incremental_daily.sql

# Backup por tenant
pg_dump -U kartenant_user -h localhost -d tenant_001 > tenant_001_backup.sql
```

### Point-in-Time Recovery

```sql
-- Restaurar a un punto específico
pg_restore -U kartenant_user -h localhost --data-only --inserts backup.sql

-- Usando WAL archives
recovery_target_time = '2025-10-15 14:30:00';
restore_command = 'cp /var/lib/postgresql/wal/%f %p';
```

---

## 📈 Optimización y Monitoring

### Queries de Monitoring

```sql
-- Tamaño de bases de datos
SELECT datname, pg_size_pretty(pg_database_size(datname))
FROM pg_database
WHERE datname LIKE 'tenant_%'
ORDER BY pg_database_size(datname) DESC;

-- Tablas más grandes
SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename))
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;

-- Queries lentas
SELECT pid, now() - pg_stat_activity.query_start AS duration, query
FROM pg_stat_activity
WHERE state = 'active' AND now() - pg_stat_activity.query_start > interval '1 minute'
ORDER BY duration DESC;
```

### Maintenance

```sql
-- Vacuum analyze semanal
VACUUM ANALYZE;

-- Reindex mensual
REINDEX DATABASE kartenant;

-- Update statistics
ANALYZE;
```

---

## 🔧 Troubleshooting

### Problemas Comunes

**Foreign Key Violations:**
```sql
-- Verificar constraints violadas
SELECT conname, conrelid::regclass, confrelid::regclass
FROM pg_constraint
WHERE contype = 'f' AND conrelid = 'products'::regclass;
```

**Deadlocks:**
```sql
-- Ver deadlocks recientes
SELECT * FROM pg_stat_database WHERE datname = 'kartenant';
```

**Bloat:**
```sql
-- Ver bloat por tabla
SELECT schemaname, tablename, n_dead_tup, n_live_tup
FROM pg_stat_user_tables
ORDER BY n_dead_tup DESC;
```

---

## 📚 Referencias

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Laravel Migrations](https://laravel.com/docs/migrations)
- [Spatie Multitenancy](https://spatie.be/docs/laravel-multitenancy)
- [Database Indexing Best Practices](https://www.postgresql.org/docs/current/indexes.html)

---

**Última actualización:** Octubre 2025
**Versión del esquema:** 2.1
**Estado:** Production Ready