# Testing Infrastructure Guide
## Browser Testing with Laravel Sail + Selenium + Chrome

**Project:** Emporio Digital
**Stack:** Laravel 11 + Laravel Sail + Selenium + Chrome Dusk
**Architecture:** Docker-based browser testing infrastructure

---

## 🏗️ System Architecture Overview

### Container Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Network                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   nginx     │  │ laravel.test│  │  selenium   │         │
│  │   (80/443)  │  │   (9000)    │  │   (4444)    │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
└─────────────────────────────────────────────────────────────┘
```

### Service Components

#### 1. **nginx** (Reverse Proxy)
- **Ports:** 80, 443
- **Purpose:** HTTP/HTTPS termination and routing
- **SSL:** Let's Encrypt certificates (production) or self-signed (development)

#### 2. **laravel.test** (Application Container)
- **Port:** 9000 (internal)
- **Purpose:** PHP application server
- **Framework:** Laravel 11 with Dusk testing capabilities
- **User:** `sail` (UID: 1000) - Critical for file permissions

#### 3. **selenium** (Browser Automation)
- **Port:** 4444
- **Purpose:** Chrome browser automation server
- **Browser:** Google Chrome with ChromeDriver
- **Network:** Linked to application for DNS resolution

---

## 🔗 The Docker Links Solution (DNS Resolution)

### The Problem: ERR_NAME_NOT_RESOLVED
When Selenium Chrome tries to access the Laravel application from within Docker containers, it cannot resolve `localhost`, `127.0.0.1`, or `laravel.test` hostnames.

### The Solution: Docker Links with Hostnames
```yaml
# docker-compose.yml
selenium:
    image: selenium/standalone-chrome
    links:
        - laravel.test:http://application.test
```

This creates an internal DNS entry where:
- **Container:** `laravel.test` → **Hostname:** `http://application.test`
- **Selenium Chrome** can now access: `http://application.test:80`

### Alternative Solutions (Not Recommended)
```yaml
# ❌ BAD: Host network mode (breaks isolation)
network_mode: "host"

# ❌ BAD: IP addresses (change between container restarts)
APP_URL: "http://172.20.0.3"
```

---

## 🚀 The "Root Maneuver" Installation Protocol

### The Problem: Permission Denied During Dusk Install
When installing Laravel Dusk inside Docker containers, you may encounter permission errors:
```bash
./vendor/bin/sail artisan dusk:install
# Error: Permission denied
```

### The Solution: The Root Maneuver Protocol
```bash
# Step 1: Enter container as root
./vendor/bin/sail root-shell

# Step 2: Install Dusk as root
php artisan dusk:install

# Step 3: Fix ownership (CRITICAL)
chown -R sail:sail /var/www/html/

# Step 4: Return to sail user
exit

# Step 5: Verify permissions
ls -la tests/Browser/
# Should show sail:sail ownership
```

### Automatic Root Maneuver Script
```bash
# Create script: scripts/install-dusk.sh
#!/bin/bash
echo "🚀 Executing Root Maneuver for Dusk installation..."

# Enter root shell and install
./vendor/bin/sail root-shell -c "
    php artisan dusk:install && \
    chown -R sail:sail /var/www/html/ && \
    echo '✅ Dusk installed and permissions fixed'
"

echo "🎉 Root Maneuver completed successfully"
```

---

## ⚙️ Complete Configuration Reference

### 1. docker-compose.yml Selenium Service
```yaml
services:
    selenium:
        image: 'selenium/standalone-chrome:${SELENIUM_VERSION:-latest}'
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        environment:
            - SE_EVENT_BUS_HOST=selenium-event-bus
            - SE_EVENT_BUS_PUBLISH_PORT=4442
            - SE_EVENT_BUS_SUBSCRIBE_PORT=4443
        links:
            - laravel.test:http://application.test
        networks:
            - sail
        depends_on:
            - laravel.test

    selenium-event-bus:
        image: 'selenium/event-bus:latest'
        networks:
            - sail

networks:
    sail:
        driver: bridge
```

### 2. tests/DuskTestCase.php Driver Configuration
```php
<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    protected static function prepare()
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver()
    {
        if (static::runningInSail()) {
            return static::seleniumDriver();
        }

        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine if the application is running in Sail.
     */
    protected static function runningInSail(): bool
    {
        return isset($_ENV['LARAVEL_SAIL']) && $_ENV['LARAVEL_SAIL'] === '1';
    }

    /**
     * Get the Selenium driver for Sail.
     */
    protected static function seleniumDriver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-web-security',
            '--allow-running-insecure-content',
            '--ignore-certificate-errors',
        ]);

        return RemoteWebDriver::create(
            'http://selenium:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }
}
```

### 3. phpunit.dusk.xml Structure
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Browser">
            <directory suffix="Test.php">./tests/Browser</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="APP_URL" value="http://application.test"/>
        <server name="BCRYPT_ROUNDS" value="4"/>
        <server name="CACHE_DRIVER" value="array"/>
        <server name="DB_CONNECTION" value="sqlite"/>
        <server name="DB_DATABASE" value=":memory:"/>
        <server name="DB_FOREIGN_KEYS" value="false"/>
        <server name="LOG_CHANNEL" value="single"/>
        <server name="MAIL_MAILER" value="array"/>
        <server name="QUEUE_CONNECTION" value="sync"/>
        <server name="SESSION_DRIVER" value="array"/>
        <server name="SESSION_ENCRYPT" value="false"/>
        <server name="TELESCOPE_ENABLED" value="false"/>
        <server name="MEMCACHED_HOST" value="127.0.0.1"/>

        <!-- Dusk-specific configuration -->
        <server name="DUSK_DRIVER_URL" value="http://selenium:4444/wd/hub"/>
        <server name="DUSK_HEADLESS_DISABLED" value="false"/>
        <server name="LARAVEL_SAIL" value="1"/>
    </php>
</phpunit>
```

---

## 🛠️ Essential Commands

### Development Commands
```bash
# Start environment with Selenium
./vendor/bin/sail up -d selenium selenium-event-bus

# Install Dusk (with Root Maneuver if needed)
./vendor/bin/sail artisan dusk:install

# Run all browser tests
./vendor/bin/sail artisan dusk

# Run specific test file
./vendor/bin/sail artisan dusk tests/Browser/LoginTest.php

# Run single test method
./vendor/bin/sail artisan dusk --filter=test_login_with_valid_credentials

# Run tests with debug output
./vendor/bin/sail artisan dusk --debug

# Run tests without headless mode (visible browser)
DUSK_HEADLESS_DISABLED=true ./vendor/bin/sail artisan dusk

# Run tests with maximized window
DUSK_START_MAXIMIZED=true ./vendor/bin/sail artisan dusk

# Generate failure screenshots and logs
./vendor/bin/sail artisan dusk --log-junit=tests/results/dusk.xml
```

### Debugging Commands
```bash
# Check Selenium status
curl http://localhost:4444/wd/hub/status

# Check container connectivity
./vendor/bin/sail exec laravel.test ping selenium

# View Selenium logs
./vendor/bin/sail logs selenium

# View Laravel test logs
./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log

# Enter Selenium container for debugging
./vendor/bin/sail exec selenium bash

# Check ChromeDriver status
./vendor/bin/sail exec selenium wget -qO- http://localhost:9515/status
```

### Maintenance Commands
```bash
# Clear all caches (important after config changes)
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# Reset Dusk environment
./vendor/bin/sail artisan dusk:uninstall
./vendor/bin/sail artisan dusk:install

# Update Selenium image
./vendor/bin/sail pull selenium/standalone-chrome:latest

# Clean up Docker resources
docker system prune -f
docker volume prune -f
```

---

## 🔧 Comprehensive Troubleshooting

### 1. Permission Denied During Dusk Install
**Error:** `Permission denied` when running `./vendor/bin/sail artisan dusk:install`

**Solution:** Apply the Root Maneuver Protocol
```bash
# Method 1: Manual Root Maneuver
./vendor/bin/sail root-shell
php artisan dusk:install
chown -R sail:sail /var/www/html/
exit

# Method 2: Automated script
./scripts/install-dusk.sh

# Method 3: Pre-installation fix
./vendor/bin/sail exec -u root laravel.test php artisan dusk:install
./vendor/bin/sail exec laravel.test sudo chown -R sail:sail .
```

### 2. DNS Resolution Errors
**Error:** `ERR_NAME_NOT_RESOLVED`, `ERR_CONNECTION_REFUSED`, or `net::ERR_NAME_NOT_RESOLVED`

**Solutions:**
```bash
# Step 1: Verify Docker Links
./vendor/bin/sail exec laravel.test ping selenium
./vendor/bin/sail exec selenium ping laravel.test

# Step 2: Check docker-compose.yml links configuration
# Ensure selenium service has:
links:
    - laravel.test:http://application.test

# Step 3: Verify APP_URL in phpunit.dusk.xml
<server name="APP_URL" value="http://application.test"/>

# Step 4: Test manual access from selenium container
./vendor/bin/sail exec selenium curl -I http://application.test

# Step 5: Check if containers are on the same network
./vendor/bin/sail exec laravel.test ip addr
./vendor/bin/sail exec selenium ip addr
```

### 3. Connection Refused Errors
**Error:** `Connection refused` or `ECONNREFUSED`

**Solutions:**
```bash
# Step 1: Check if Selenium is running
./vendor/bin/sail ps | grep selenium

# Step 2: Start Selenium containers
./vendor/bin/sail up -d selenium selenium-event-bus

# Step 3: Check Selenium logs for startup issues
./vendor/bin/sail logs selenium

# Step 4: Verify Selenium is accessible
curl http://localhost:4444/wd/hub/status

# Step 5: Check port conflicts
netstat -tlnp | grep 4444
```

### 4. Class Not Found Errors
**Error:** `Class 'Tests\Browser\LoginTest' not found`

**Solutions:**
```bash
# Step 1: Run Dusk installation
./vendor/bin/sail artisan dusk:install

# Step 2: Check file permissions
ls -la tests/Browser/
# Should show sail:sail ownership

# Step 3: Fix permissions if needed
./vendor/bin/sail exec -u root laravel.test chown -R sail:sail tests/

# Step 4: Verify composer autoloader
./vendor/bin/sail composer dump-autoload

# Step 5: Clear PHPUnit cache
./vendor/bin/sail artisan test --clear-cache
```

### 5. Chrome Crashes
**Error:** Chrome browser crashes during tests

**Solutions:**
```bash
# Step 1: Update Chrome options in DuskTestCase.php
protected static function seleniumDriver()
{
    $options = (new ChromeOptions)->addArguments([
        '--disable-gpu',
        '--headless=new',
        '--no-sandbox',
        '--disable-dev-shm-usage',  // Critical for Docker
        '--disable-extensions',
        '--disable-web-security',
        '--allow-running-insecure-content',
        '--ignore-certificate-errors',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-renderer-backgrounding',
        '--disable-features=TranslateUI',
        '--disable-ipc-flooding-protection',
    ]);

    // ... rest of the method
}

# Step 2: Restart containers with more memory
# Edit docker-compose.yml:
services:
    selenium:
        shm_size: 2gb

# Step 3: Use non-headless mode for debugging
DUSK_HEADLESS_DISABLED=true ./vendor/bin/sail artisan dusk

# Step 4: Check Selenium logs for Chrome errors
./vendor/bin/sail logs selenium | grep -i chrome
```

### 6. SSL Certificate Errors
**Error:** `NET::ERR_CERT_AUTHORITY_INVALID` or SSL handshake issues

**Solutions:**
```bash
# Step 1: Add Chrome options to ignore SSL errors
$options->addArguments([
    '--ignore-certificate-errors',
    '--ignore-ssl-errors',
    '--ignore-certificate-errors-spki-list',
]);

// Or programmatically:
DesiredCapabilities::chrome()->setCapability(
    'acceptInsecureCerts', true
);

# Step 2: Use HTTP for testing
<server name="APP_URL" value="http://application.test"/>

# Step 3: Install certificates in Selenium container
./vendor/bin/sail exec selenium update-ca-certificates

# Step 4: For HTTPS testing, mount certificates:
# In docker-compose.yml:
volumes:
    - ./certs:/etc/ssl/certs:ro
```

### 7. Memory and Performance Issues
**Error:** Tests are slow, timeouts, or memory issues

**Solutions:**
```bash
# Step 1: Increase shared memory
# In docker-compose.yml:
services:
    selenium:
        shm_size: 2gb

# Step 2: Optimize Chrome options
$options->addArguments([
    '--memory-pressure-off',
    '--max_old_space_size=4096',
    '--disable-background-timer-throttling',
    '--disable-field-trial-config',
    '--disable-backgrounding-occluded-windows',
]);

# Step 3: Increase PHP memory limits
# In php.ini or .htaccess:
memory_limit = 512M

# Step 4: Use parallel testing
./vendor/bin/sail artisan dusk --parallel
```

---

## 📋 Best Practices for Test Organization

### 1. Base Test Classes
```php
// tests/Browser/Pages/Page.php (Base Page)
abstract class Page
{
    protected static $url = '';

    public static function url()
    {
        return static::$url;
    }

    public function assert(Browser $browser)
    {
        // Common assertions for all pages
    }
}

// tests/Browser/LoginPage.php
class LoginPage extends Page
{
    protected static $url = '/login';

    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url())
                ->assertSeeIn('h1', 'Login')
                ->assertVisible('@email')
                ->assertVisible('@password');
    }
}
```

### 2. Data Factories
```php
// database/factories/UserFactory.php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
```

### 3. Reusable Test Traits
```php
// tests/Browser/Concerns/AuthenticatesUsers.php
trait AuthenticatesUsers
{
    protected function loginAs(Browser $browser, $user)
    {
        $browser->visit('/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/', 5)
                ->assertAuthenticated();
    }

    protected function logout(Browser $browser)
    {
        $browser->click('@user-dropdown')
                ->clickLink('Logout')
                ->waitForLocation('/login', 5)
                ->assertGuest();
    }
}
```

### 4. Timeouts and Waits
```php
// Global timeouts in DuskTestCase.php
protected function setUp(): void
{
    parent::setUp();

    // Set default timeout
    $this->browse(function (Browser $browser) {
        $browser->driver->manage()->timeouts()->implicitlyWait = 10; // seconds
    });
}

// In individual tests
$browser->waitForText('Welcome', 5)
        ->waitForVisible('@submit-button', 3)
        ->waitUntilMissing('.loading-spinner', 10);
```

### 5. Screen Elements
```php
// tests/Browser/Pages/LoginPage.php
public function elements()
{
    return [
        '@email' => 'input[name="email"]',
        '@password' => 'input[name="password"]',
        '@submit-button' => 'button[type="submit"]',
        '@remember-me' => 'input[name="remember"]',
    ];
}
```

---

## 🔗 Reference Links and Resources

### Official Documentation
- **Laravel Dusk:** https://laravel.com/docs/dusk
- **Selenium WebDriver:** https://www.selenium.dev/documentation/
- **Docker Compose:** https://docs.docker.com/compose/
- **Chrome DevTools Protocol:** https://chromedevtools.github.io/devtools-protocol/

### Key Community Resources
- **Laravel Sail:** https://laravel.com/docs/sail
- **Laravel Testing:** https://laravel.com/docs/testing
- **PHPUnit:** https://phpunit.de/documentation.html
- **ChromeDriver:** https://chromedriver.chromium.org/

### Troubleshooting Resources
- **Docker Troubleshooting:** https://docs.docker.com/config/troubleshooting/
- **Selenium Issues:** https://github.com/SeleniumHQ/selenium/issues
- **Laravel Dusk Issues:** https://github.com/laravel/dusk/issues

---

## 📝 Final Notes

### Proven Infrastructure
This testing infrastructure has been **battle-tested** in production environments and has proven to be:

- **Stable:** Consistent performance across multiple projects
- **Scalable:** Handles hundreds of browser tests efficiently
- **Maintainable:** Simple setup and minimal ongoing maintenance
- **Reliable:** Low false-positive rate and consistent results

### Future Considerations
1. **Parallel Testing:** Consider Laravel Dusk's parallel testing features for large test suites
2. **CI/CD Integration:** This infrastructure integrates seamlessly with GitHub Actions, GitLab CI, or Jenkins
3. **Mobile Testing:** The same pattern can be extended with Appium for mobile browser testing
4. **Visual Regression:** Consider adding visual regression testing with tools like Percy or Applitools

### Key Success Factors
1. **Proper DNS Resolution:** The Docker Links solution is critical
2. **Permission Management:** Always maintain sail user ownership
3. **Chrome Optimization:** Use appropriate Chrome flags for Docker environments
4. **Resource Allocation:** Ensure adequate memory and shared memory for Selenium
5. **Test Organization:** Use base classes, factories, and reusable components

This infrastructure represents a production-ready solution for browser testing in Laravel applications using Docker, providing reliability, scalability, and maintainability for development teams.
