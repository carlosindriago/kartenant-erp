# Arquitectura de Migraciones

## Dos Universos Separados

### LANDLORD (database/migrations/landlord/)
- Sistema central
- Usuarios, tenants, permisos superadmin
- Guard: superadmin

### TENANT (database/migrations/tenant/)  
- Datos de negocio
- Productos, ventas, permisos tenant
- Guard: web/tenant

## Configuración

AppServiceProvider.php:
```php
$this->loadMigrationsFrom([database_path('migrations/landlord')]);
```

config/multitenancy.php:
```php
'tenant_migrations_path' => database_path('migrations/tenant'),
```

## Tablas Compartidas

Algunas tablas necesitan existir en AMBOS universos:

### activity_log
- **Landlord:** Auditoría del panel admin (/admin)
- **Tenant:** Auditoría de acciones de usuarios del cliente (/app)
- **Configuración:** AppServiceProvider define `activitylog.database_connection = 'landlord'` para contexto admin

## Importante
- Nunca mezclar migraciones
- Cada universo tiene sus propios IDs
- Evita contaminación de contexto
- Algunas tablas (como activity_log) pueden existir en ambos lados con propósitos distintos
