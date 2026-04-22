# Permission Fix Script - Complete Guide

## 🚨 Overview

The `fix-permissions.sh` script is designed to resolve common permission issues in the Emporio Digital Laravel project that occur when files are created with different user ownership (e.g., Docker container user 1337 vs host user carlos).

## 📋 Table of Contents

- [Quick Usage](#quick-usage)
- [Common Permission Issues](#common-permission-issues)
- [Detailed Usage](#detailed-usage)
- [What the Script Fixes](#what-the-script-fixes)
- [Troubleshooting](#troubleshooting)
- [Prevention Tips](#prevention-tips)

## 🚀 Quick Usage

### Basic Usage
```bash
# Fix all permissions with default settings
./fix-permissions.sh

# Show what would be changed without executing (safe)
./fix-permissions.sh --dry-run

# Fix with detailed output
./fix-permissions.sh --verbose
```

### When to Use This Script

Run this script when you encounter:
- ❌ `Permission denied` when running `./artisan` commands
- ❌ Laravel Sail permission errors (`./vendor/bin/sail`)
- ❌ File upload permission issues
- ❌ Cache/log write permission errors
- ❌ Git permission conflicts
- ❌ `EACCES: permission denied` errors

## 🔍 Common Permission Issues

### 1. Docker Container User Mismatch
```bash
# Error example
./vendor/bin/sail artisan migrate
# bash: ./vendor/bin/sail: Permission denied

# Solution
./fix-permissions.sh
```

### 2. Laravel Artisan Permission Errors
```bash
# Error example
php artisan cache:clear
# Error: Failed to clear cache. Permission denied

# Solution
./fix-permissions.sh
```

### 3. File Upload/Write Issues
```bash
# Error example in logs
storage/logs/laravel.log: Permission denied

# Solution
./fix-permissions.sh
```

## 📖 Detailed Usage

### Command Line Options

| Option | Description | Example |
|--------|-------------|---------|
| `-v, --verbose` | Show detailed output of all operations | `./fix-permissions.sh -v` |
| `-d, --dry-run` | Show what would be done without executing | `./fix-permissions.sh -d` |
| `-h, --help` | Show help message | `./fix-permissions.sh -h` |

### Usage Examples

```bash
# Safe: See what would be changed
./fix-permissions.sh --dry-run

# Verbose: See all operations
./fix-permissions.sh --verbose

# Combined: Verbose dry run
./fix-permissions.sh -v -d

# Help: Show all options
./fix-permissions.sh --help
```

## 🔧 What the Script Fixes

### Step-by-Step Process

1. **Project Ownership** (`chown -R user:group .`)
   - Fixes ownership of all files to current user
   - Resolves Docker container user conflicts

2. **File Permissions** (`find . -type f -exec chmod 644 {} \;`)
   - Sets all files to 644 (read/write for owner, read for others)
   - Standard secure file permissions

3. **Directory Permissions** (`find . -type d -exec chmod 755 {} \;`)
   - Sets all directories to 755 (read/write/execute for owner, read/execute for others)
   - Standard directory permissions

4. **Laravel Writable Directories** (`chmod -R 775 storage/ bootstrap/cache/`)
   - Special permissions for Laravel's writable directories
   - Sets group to www-data for web server access

5. **Executable Scripts** (`find . -name '*.sh' -exec chmod +x {} \;`)
   - Makes all shell scripts executable
   - Fixes artisan, sail, and custom scripts

6. **Laravel Sail Permissions**
   - Special handling for Laravel Sail executable
   - Fixes Docker-related permission issues

7. **Critical Laravel Files**
   - Ensures artisan, composer.json, composer.lock are executable
   - Essential files for Laravel operation

8. **Cache Clearing**
   - Automatically clears Laravel caches after fixing permissions
   - Ensures clean state

## 🐛 Troubleshooting

### Issue: Script can't run
```bash
# Error
./fix-permissions.sh
# bash: ./fix-permissions.sh: Permission denied

# Solution
chmod +x fix-permissions.sh
```

### Issue: Permission denied despite running script
```bash
# If you still get permission errors, try running with explicit user
sudo chown -R $USER:$USER .
./fix-permissions.sh
```

### Issue: Docker-specific issues
```bash
# For Docker-specific permission issues
./fix-permissions.sh --verbose
# Check if www-data group exists and is set correctly
```

### Issue: Git conflicts after permission fix
```bash
# If git shows many modified files after permission fix
git status
git config core.filemode false  # Optional: disable file mode checks
git checkout -- .               # Reset file permissions
```

## 🛡️ Prevention Tips

### 1. Use Laravel Sail Properly
```bash
# ✅ CORRECT: Always use sail for container operations
./vendor/bin/sail artisan make:controller MyController
./vendor/bin/sail composer require package/name

# ❌ INCORRECT: Never use docker exec directly
docker exec -it laravel.test php artisan make:controller
```

### 2. Fix Permissions Immediately
```bash
# If you accidentally run commands as root or wrong user
sudo chown -R $USER:$USER .
./fix-permissions.sh
```

### 3. Use Stash for Git Conflicts
```bash
# Before switching branches with permission issues
git stash push -m "permission fix needed"
git checkout other-branch
# Fix permissions if needed, then pop stash
git stash pop
```

### 4. Regular Maintenance
```bash
# Run after major development sessions
./fix-permissions.sh
./vendor/bin/sail artisan optimize:clear
```

## 📁 File Structure Impact

The script safely modifies these areas:
- ✅ All project files (ownership and permissions)
- ✅ `storage/` directory (writable by web server)
- ✅ `bootstrap/cache/` directory (Laravel cache)
- ✅ `vendor/bin/sail` executable
- ✅ Shell scripts (`.sh` files)
- ✅ Laravel critical files (`artisan`, `composer.json`)

The script AVOIDS modifying:
- ❌ Database files
- ❌ External system files
- ❌ User configuration files outside project

## 🚨 Important Notes

### Security Considerations
- Script uses `sudo` for ownership changes
- Only run in trusted project directories
- Review script before running with elevated privileges

### Project Compatibility
- Designed specifically for Laravel 11 projects
- Tested with Docker/Laravel Sail environments
- Compatible with Ubuntu/Debian-based systems

### When NOT to Use
- ❌ Production servers (use specific server setup procedures)
- ❌ Non-Laravel projects
- ❌ Systems with custom permission requirements

## 🔗 Related Documentation

- **CLAUDE.md** - Main project documentation
- **Docker Permission Best Practices** section in CLAUDE.md
- **Laravel Sail Documentation** - https://laravel.com/docs/sail
- **Linux File Permissions** - man pages for `chmod`, `chown`

## 📞 Support

If you encounter issues not covered in this guide:

1. Check the script output with `--verbose` flag
2. Run with `--dry-run` first to see planned changes
3. Consult the main project documentation
4. Review Laravel and Docker documentation

## 🎯 Quick Reference

```bash
# Emergency fix (most common usage)
./fix-permissions.sh

# Safe preview
./fix-permissions.sh --dry-run

# Detailed operation
./fix-permissions.sh --verbose

# Help
./fix-permissions.sh --help
```

---

**Remember**: This script is a safety net. The best practice is to avoid permission issues by using the correct Docker commands and user contexts from the start!