# Docker File Permissions Prevention Strategy

## Issue Summary
Files created with restricted permissions (600) in Docker environments can prevent the web server from reading view files, causing `file_get_contents(): Permission denied` errors.

## Root Cause
In Docker/Sail environments, files created as the host user may have restrictive permissions that prevent the web server process (running as a different user) from accessing them.

## Prevention Guidelines

### 1. File Creation Best Practices

**ALWAYS use Laravel Sail for file operations:**
```bash
# ✅ CORRECT - Creates files with proper permissions
./vendor/bin/sail exec -u www-data touch resources/views/new-file.blade.php

# ✅ CORRECT - Use Sail for artisan commands that create files
./vendor/bin/sail artisan make:controller NewController

# ❌ INCORRECT - Creates files with restrictive permissions
touch resources/views/new-file.blade.php
```

### 2. Permission Fix Commands

**Fix existing permission issues:**
```bash
# Fix specific file
chmod 644 path/to/file.blade.php

# Fix all Blade views
find resources/views -name "*.blade.php" -exec chmod 644 {} \;

# Verify permissions
find resources/views -name "*.blade.php" -exec ls -la {} \;
```

### 3. Proper File Permissions

**Development Environment:**
- View files: `644` (-rw-r--r--)
- Config files: `644` (-rw-r--r--)
- PHP files: `644` (-rw-r--r--)
- Directories: `755` (drwxr-xr-x)

**Production Environment:**
- Same as development, but ensure web server user owns the files
- Example: `www-data:www-data` or `nginx:nginx`

### 4. Monitoring Commands

**Regular permission audits:**
```bash
# Check for view files with incorrect permissions
find resources/views -name "*.blade.php" -exec ls -la {} \; | grep "^-rw-------"

# Count problematic files
find resources/views -name "*.blade.php" -exec ls -la {} \; | grep "^-rw-------" | wc -l
```

### 5. Docker Volume Mount Configuration

**Ensure proper volume mounts in docker-compose.yml:**
```yaml
volumes:
  - ./:/var/www/html
  - /var/www/html/storage
  - /var/www/html/bootstrap/cache
```

### 6. Emergency Recovery

**If permission issues occur:**
```bash
# 1. Clear all caches
./vendor/bin/sail artisan optimize:clear

# 2. Fix all view permissions
find resources/views -name "*.blade.php" -exec chmod 644 {} \;

# 3. Restart containers
./vendor/bin/sail down && ./vendor/bin/sail up -d
```

## Detection Checklist

- [ ] Files created outside of Sail commands
- [ ] Files with permissions 600 (rw-------)
- [ ] `file_get_contents(): Permission denied` errors in logs
- [ ] View files not found or accessible

## Implementation Status

- ✅ Fixed: `/resources/views/resources/tenant-resource/pages/view-tenant.blade.php`
- ✅ Fixed: `/resources/views/filament/resources/tenant-resource/pages/view-tenant.blade.php`
- ✅ Cleared all Laravel caches
- ✅ Verified accessibility of view files

## Training Guidelines

1. **Always use Sail** for any file creation operations
2. **Verify permissions** after creating new view files
3. **Clear caches** after permission changes
4. **Document permission issues** to prevent recurrence

## Related Documentation

- [CLAUDE.md - Docker Permissions Protocol](./CLAUDE.md)
- [Docker Laravel Sail Documentation](https://laravel.com/docs/sail)
- [Linux File Permissions](https://en.wikipedia.org/wiki/File_system_permissions)