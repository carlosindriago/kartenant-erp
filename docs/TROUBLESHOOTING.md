# Solución de Problemas Comunes

Esta guía documenta problemas comunes que pueden surgir durante la instalación y el desarrollo del entorno local, junto con sus soluciones.

---

## 1. Error 500 en la carga inicial (Vite Manifest Not Found)

-   **Síntoma:** Al acceder a la web por primera vez, aparece una página de error 500 de Laravel y el log (`storage/logs/laravel.log`) muestra un error `ViteManifestNotFoundException`.
-   **Causa:** Los archivos de frontend (JavaScript y CSS) no se han compilado. Esto puede deberse a un error en el código o a que simplemente no se ha ejecutado el comando de compilación.
-   **Solución:**
    1.  Abre una terminal y ejecuta el comando de compilación de producción. Este comando intentará compilar los assets y te mostrará cualquier error de sintaxis que pueda existir:
        ```bash
        ./vendor/bin/sail npm run build
        ```
    2.  Si hay un error, corrígelo en el archivo JS correspondiente.
    3.  Una vez corregido, vuelve a ejecutar `npm run build` hasta que finalice con éxito.
    4.  Para el desarrollo continuo, puedes usar el siguiente comando que se actualiza automáticamente al detectar cambios en los archivos:
        ```bash
        ./vendor/bin/sail npm run dev
        ```

---

## 2. Error de Timeout (`ERR_TIMED_OUT` o `Connection reset by peer`)

-   **Síntoma:** El navegador no puede acceder a `https://emporiodigital.test` y muestra un error de timeout. Al usar `curl`, se recibe un error de "Connection reset by peer".
-   **Causa:** Este es un problema de red a bajo nivel, específico de la máquina anfitrión (host), que impide que Docker gestione correctamente el tráfico en puertos privilegiados como el **443 (HTTPS)**. Puede deberse a conflictos con otro software, reglas de firewall residuales o un problema en la propia capa de red de Docker.
-   **Solución / Workaround:**
    1.  **Confirmar el problema:** Verifica que la aplicación funciona correctamente dentro de Docker ejecutando un `curl` interno. Si este comando devuelve código HTML, el problema está en la comunicación host-contenedor.
        ```bash
        ./vendor/bin/sail exec laravel.test curl -k -H "Host: emporiodigital.test" https://nginx
        ```
    2.  **Solución recomendada (Workaround):** Cambia el puerto en el archivo `docker-compose.yml` para usar uno no privilegiado.
        -   Cambia la línea `- '443:443'` a `- '8443:443'` bajo el servicio `nginx`.
        -   Aplica los cambios con: `./vendor/bin/sail up -d`
        -   Accede a la web a través de: **`https://emporiodigital.test:8443`**
    3.  **Solución a largo plazo:** Diagnostica el conflicto en la máquina host.
        -   Reinicia el servicio de Docker: `sudo systemctl restart docker`
        -   Busca otros procesos en el puerto 443: `sudo lsof -i :443`

---

## 3. Aviso de "Conexión No Segura" en el Navegador

-   **Síntoma:** El navegador muestra una advertencia de privacidad y dice que el certificado SSL no es de confianza.
-   **Causa:** Es un comportamiento normal y esperado. El certificado SSL del proyecto es para desarrollo y está autofirmado por una herramienta llamada `mkcert`. Los navegadores no confían en esta "autoridad" por defecto.
-   **Solución:**
    -   **Opción Rápida:** En la página de advertencia, haz clic en "Avanzado" y luego en "Proceder a emporiodigital.test (no seguro)".
    -   **Opción Permanente (Recomendado):** Instala la Autoridad de Certificación (CA) de `mkcert` en tu sistema para que todos los certificados que genere sean automáticamente de confianza. Si tienes `mkcert` instalado, solo tienes que ejecutar este comando una vez:
        ```bash
        mkcert -install
        ```

---

## 4. Error al Crear Tiendas (FATAL: database "sail" does not exist)

-   **Síntoma:** Al intentar crear una nueva tienda (tenant) desde el panel de administración, la aplicación falla con un error `Illuminate\Database\QueryException` que indica que la base de datos "sail" no existe.
-   **Causa:** Laravel está utilizando una configuración de base de datos antigua que tiene guardada en caché, en lugar de usar la configuración actualizada del archivo `.env`.
-   **Solución:** Limpia la caché de configuración de Laravel con el siguiente comando de Artisan. Esto obliga a la aplicación a leer de nuevo los archivos `.env` y de configuración.
    ```bash
    ./vendor/bin/sail artisan config:clear
    ```
    Después de ejecutarlo, intenta crear la tienda de nuevo.
