@auth
<div x-data="{
    open: false,
    severity: 'medium',
    title: '',
    description: '',
    steps: '',
    submitting: false,

    async submit(event) {
        if (!this.title || !this.description) {
            alert('Por favor completa al menos el título y la descripción');
            return;
        }

        this.submitting = true;

        try {
            const formData = new FormData(event.target);

            // Set form fields explicitly
            formData.set('severity', this.severity);
            formData.set('title', this.title);
            formData.set('description', this.description);
            formData.set('steps', this.steps);
            formData.set('_token', '{{ csrf_token() }}');

            const response = await fetch('/app/bug-report', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                    // NO incluir X-CSRF-TOKEN aquí cuando usamos FormData con _token
                }
            });

            // Check if response is ok before parsing JSON
            if (!response.ok) {
                const text = await response.text();
                console.error('Server response:', text);
                throw new Error('Error del servidor: ' + response.status);
            }

            const result = await response.json();

            if (result.success) {
                alert('✅ Reporte enviado exitosamente. ¡Gracias por tu ayuda!');
                this.title = '';
                this.description = '';
                this.steps = '';
                this.severity = 'medium';
                event.target.reset(); // Reset file inputs
                this.open = false;
            } else {
                alert('❌ Error: ' + result.message);
            }
        } catch (error) {
            alert('❌ Error al enviar el reporte: ' + error.message);
        } finally {
            this.submitting = false;
        }
    }
}">
    {{-- Floating Button --}}
    <div style="position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;">
        <button
            type="button"
            @click="open = true"
            style="
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
                background-color: rgb(220, 38, 38);
                color: white;
                border-radius: 9999px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                border: none;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s;
            "
            onmouseover="this.style.opacity='0.9'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'"
            onmouseout="this.style.opacity='1'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)'"
            title="Reportar un Problema"
        >
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Reportar Problema</span>
        </button>
    </div>

    {{-- Modal --}}
    <div
        x-show="open"
        x-cloak
        style="position: fixed; inset: 0; z-index: 10000; overflow-y: auto;"
        @click.self="open = false"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="dark:bg-gray-950/75 bg-gray-900/50"
            style="position: fixed; inset: 0;"
        ></div>

        {{-- Modal Content --}}
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100%; padding: 1rem;">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="fi-modal-window dark:bg-gray-900 bg-white"
                style="
                    border-radius: 0.5rem;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    width: 100%;
                    max-width: 42rem;
                    padding: 1.5rem;
                    position: relative;
                "
                @click.stop
            >
                {{-- Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg class="dark:text-red-400 text-red-600" style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h2 class="dark:text-gray-100 text-gray-900" style="font-size: 1.25rem; font-weight: 600;">Reportar un Problema</h2>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="dark:text-gray-400 text-gray-500 dark:hover:text-gray-300 hover:text-gray-700"
                        style="cursor: pointer; background: none; border: none; padding: 0.25rem; transition: color 0.2s;"
                    >
                        <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p class="dark:text-gray-400 text-gray-600" style="margin-bottom: 1.5rem;">
                    Describe el problema que estás experimentando. Tu reporte será enviado directamente al equipo de soporte.
                </p>

                {{-- Form --}}
                <form @submit.prevent="submit">
                    {{-- Severity --}}
                    <div style="margin-bottom: 1rem;">
                        <label class="dark:text-gray-300 text-gray-700" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                            Gravedad
                        </label>
                        <select
                            x-model="severity"
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 bg-white border-gray-300 text-gray-900"
                            style="width: 100%; border-width: 1px; border-radius: 0.375rem; padding: 0.5rem; font-size: 0.875rem;"
                        >
                            <option value="low">🟢 Baja - Problema menor</option>
                            <option value="medium" selected>🟡 Media - Afecta funcionalidad</option>
                            <option value="high">🟠 Alta - Bloquea tareas importantes</option>
                            <option value="critical">🔴 Crítica - Sistema inaccesible</option>
                        </select>
                    </div>

                    {{-- Title --}}
                    <div style="margin-bottom: 1rem;">
                        <label class="dark:text-gray-300 text-gray-700" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                            Título <span style="color: rgb(220, 38, 38);">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="title"
                            placeholder="Ej: Error al guardar una venta"
                            required
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:placeholder-gray-500 bg-white border-gray-300 text-gray-900 placeholder-gray-400"
                            style="width: 100%; border-width: 1px; border-radius: 0.375rem; padding: 0.5rem; font-size: 0.875rem;"
                        />
                    </div>

                    {{-- Description --}}
                    <div style="margin-bottom: 1rem;">
                        <label class="dark:text-gray-300 text-gray-700" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                            Descripción <span style="color: rgb(220, 38, 38);">*</span>
                        </label>
                        <textarea
                            x-model="description"
                            rows="3"
                            placeholder="Describe qué pasó y qué esperabas que pasara..."
                            required
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:placeholder-gray-500 bg-white border-gray-300 text-gray-900 placeholder-gray-400"
                            style="width: 100%; border-width: 1px; border-radius: 0.375rem; padding: 0.5rem; font-size: 0.875rem; resize: vertical;"
                        ></textarea>
                    </div>

                    {{-- Steps --}}
                    <div style="margin-bottom: 1rem;">
                        <label class="dark:text-gray-300 text-gray-700" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                            Pasos para reproducir (opcional)
                        </label>
                        <textarea
                            x-model="steps"
                            rows="3"
                            placeholder="1. Ir a...&#10;2. Hacer clic en...&#10;3. Ver error..."
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:placeholder-gray-500 bg-white border-gray-300 text-gray-900 placeholder-gray-400"
                            style="width: 100%; border-width: 1px; border-radius: 0.375rem; padding: 0.5rem; font-size: 0.875rem; resize: vertical;"
                        ></textarea>
                    </div>

                    {{-- Screenshots --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label class="dark:text-gray-300 text-gray-700" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                            Capturas de pantalla (opcional)
                        </label>
                        <input
                            type="file"
                            name="screenshots[]"
                            accept="image/*"
                            multiple
                            @change="
                                if ($event.target.files.length > 3) {
                                    alert('Máximo 3 archivos permitidos');
                                    $event.target.value = '';
                                }
                            "
                            class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:file:bg-gray-700 dark:file:text-gray-300 bg-white border-gray-300 text-gray-900 file:bg-gray-100 file:text-gray-700"
                            style="
                                width: 100%;
                                border-width: 1px;
                                border-radius: 0.375rem;
                                padding: 0.5rem;
                                font-size: 0.875rem;
                                cursor: pointer;
                            "
                        />
                        <p class="dark:text-gray-500 text-gray-500" style="font-size: 0.75rem; margin-top: 0.25rem;">
                            Máximo 3 archivos, 5MB cada uno. Formatos: JPG, PNG, GIF
                        </p>
                    </div>

                    {{-- Footer --}}
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button
                            type="button"
                            @click="open = false"
                            class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                            style="
                                padding: 0.5rem 1rem;
                                border-width: 1px;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                cursor: pointer;
                                transition: all 0.2s;
                            "
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="dark:bg-red-600 dark:hover:bg-red-700 bg-red-600 hover:bg-red-700"
                            style="
                                padding: 0.5rem 1rem;
                                border: none;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                color: white;
                                cursor: pointer;
                                transition: all 0.2s;
                            "
                            :style="submitting ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                        >
                            <span x-show="!submitting">Enviar Reporte</span>
                            <span x-show="submitting">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endauth
