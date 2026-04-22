@extends('tenant.layouts.app')

@section('title', 'Configuración de la Tienda')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Configuración de la Tienda</h1>
                <p class="text-gray-600 mt-2">Personaliza la apariencia y datos de tu tienda</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('tenant.settings.preview') }}" target="_blank"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Vista Previa
                </a>
            </div>
        </div>
    </div>

    <!-- Storage Usage Alert -->
    @if($storageUsage['file_count'] > 0)
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-blue-800">
                <span class="font-medium">Uso de almacenamiento:</span>
                {{ $storageUsage['file_count'] }} archivos, {{ $storageUsage['total_size_human'] }}
            </div>
        </div>
    </div>
    @endif

    <form id="settingsForm" class="space-y-8">
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Información Básica
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="store_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre de la Tienda <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="store_name" name="store_name" value="{{ $settings->store_name }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required maxlength="255">
                    <p class="text-xs text-gray-500 mt-1">Este nombre aparecerá en tu página pública</p>
                </div>

                <div>
                    <label for="store_slogan" class="block text-sm font-medium text-gray-700 mb-2">
                        Eslogan de la Tienda
                    </label>
                    <input type="text" id="store_slogan" name="store_slogan" value="{{ $settings->store_slogan }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           maxlength="255" placeholder="Tu eslogan aquí">
                    <p class="text-xs text-gray-500 mt-1">Un eslogan atractivo para tus clientes</p>
                </div>

                <div>
                    <label for="welcome_message" class="block text-sm font-medium text-gray-700 mb-2">
                        Mensaje de Bienvenida
                    </label>
                    <textarea id="welcome_message" name="welcome_message" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              maxlength="1000" placeholder="¡Bienvenido a mi tienda!">{{ $settings->welcome_message }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Mensaje que verán tus visitantes</p>
                </div>

                <div>
                    <label for="brand_color" class="block text-sm font-medium text-gray-700 mb-2">
                        Color Principal de la Marca
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" id="brand_color" name="brand_color" value="{{ $settings->brand_color }}"
                               class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                        <input type="text" id="brand_color_text" name="brand_color_text" value="{{ $settings->brand_color }}"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="#2563eb" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">El color principal de tu marca</p>
                </div>

                <div>
                    <label for="primary_font" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipografía Principal
                    </label>
                    <select id="primary_font" name="primary_font"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Inter" {{ $settings->primary_font === 'Inter' ? 'selected' : '' }}>Inter</option>
                        <option value="Roboto" {{ $settings->primary_font === 'Roboto' ? 'selected' : '' }}>Roboto</option>
                        <option value="Open Sans" {{ $settings->primary_font === 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                        <option value="Poppins" {{ $settings->primary_font === 'Poppins' ? 'selected' : '' }}>Poppins</option>
                        <option value="Montserrat" {{ $settings->primary_font === 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">La tipografía principal de tu tienda</p>
                </div>
            </div>

            <!-- Toggles -->
            <div class="flex items-center space-x-6 mt-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" {{ $settings->is_active ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Tienda activa públicamente</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="show_background_image" {{ $settings->show_background_image ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Mostrar imagen de fondo</span>
                </label>
            </div>
        </div>

        <!-- Images Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Imágenes de la Tienda
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Logo Upload -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Logo de la Tienda</h3>

                    @if($settings->hasLogo())
                    <div class="mb-4">
                        <div class="relative inline-block">
                            <img src="{{ $settings->getLogoPublicUrl() }}" alt="Logo actual"
                                 class="h-32 w-auto max-w-full rounded-lg border border-gray-300">
                            <button type="button" onclick="deleteImage('logo')"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Tamaño: {{ $settings->getLogoSize() }}</p>
                    </div>
                    @endif

                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
                         id="logoDropZone">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <div class="mt-4">
                            <label for="logo" class="cursor-pointer">
                                <span class="text-blue-600 hover:text-blue-500 font-medium">Subir logo</span>
                                <input type="file" id="logo" name="logo" accept="image/*" class="hidden" onchange="handleLogoUpload(this)">
                                <span class="text-gray-500"> o arrastra el archivo aquí</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG o WebP • Máx 2MB • Recomendado 400x400px</p>
                    </div>

                    <div id="logoProgress" class="hidden mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">Subiendo logo...</p>
                    </div>
                </div>

                <!-- Background Upload -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Imagen de Fondo</h3>

                    @if($settings->hasBackground())
                    <div class="mb-4">
                        <div class="relative inline-block">
                            <img src="{{ $settings->getBackgroundPublicUrl() }}" alt="Fondo actual"
                                 class="h-32 w-auto max-w-full rounded-lg border border-gray-300">
                            <button type="button" onclick="deleteImage('background')"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Tamaño: {{ $settings->getBackgroundSize() }}</p>
                    </div>
                    @endif

                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
                         id="backgroundDropZone">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="mt-4">
                            <label for="background" class="cursor-pointer">
                                <span class="text-blue-600 hover:text-blue-500 font-medium">Subir fondo</span>
                                <input type="file" id="background" name="background" accept="image/*" class="hidden" onchange="handleBackgroundUpload(this)">
                                <span class="text-gray-500"> o arrastra el archivo aquí</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG o WebP • Máx 5MB • Mínimo 1200x800px</p>
                    </div>

                    <div id="backgroundProgress" class="hidden mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">Subiendo imagen de fondo...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Información de Contacto
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de WhatsApp
                    </label>
                    <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ $settings->whatsapp_number }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="+52 1 23 4567 8900" maxlength="20">
                    <p class="text-xs text-gray-500 mt-1">Clientes podrán contactarte por WhatsApp</p>
                </div>

                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Correo de Contacto
                    </label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ $settings->contact_email }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="contacto@mitienda.com" maxlength="255">
                    <p class="text-xs text-gray-500 mt-1">Correo público para consultas</p>
                </div>

                <div>
                    <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL de Facebook
                    </label>
                    <input type="url" id="facebook_url" name="facebook_url" value="{{ $settings->facebook_url }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://facebook.com/tutienda" maxlength="500">
                    <p class="text-xs text-gray-500 mt-1">Enlace a tu página de Facebook</p>
                </div>

                <div>
                    <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL de Instagram
                    </label>
                    <input type="url" id="instagram_url" name="instagram_url" value="{{ $settings->instagram_url }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://instagram.com/tutienda" maxlength="500">
                    <p class="text-xs text-gray-500 mt-1">Enlace a tu perfil de Instagram</p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                Los cambios se guardarán automáticamente
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Success/Error Notifications -->
<div id="notification" class="fixed bottom-4 right-4 transform translate-x-full transition-transform duration-300 z-50"></div>
@endsection

@push('styles')
<style>
/* File upload drag and drop styles */
.drag-active {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
}

/* Color picker sync */
input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

/* Notification styles */
.notification-success {
    background-color: #10b981;
    color: white;
}

.notification-error {
    background-color: #ef4444;
    color: white;
}
</style>
@endpush

@push('scripts')
<script>
// Sync color picker and text input
document.getElementById('brand_color').addEventListener('input', function(e) {
    document.getElementById('brand_color_text').value = e.target.value;
});

document.getElementById('brand_color_text').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('brand_color').value = e.target.value;
    }
});

// Form submission
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    // Convert checkboxes to boolean
    data.is_active = formData.has('is_active');
    data.show_background_image = formData.has('show_background_image');

    try {
        const response = await fetch('{{ route("tenant.settings.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message || 'Error al guardar los cambios', 'error');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        showNotification('Error de conexión. Intente nuevamente.', 'error');
        console.error('Network error:', error);
    }
});

// File upload handlers
async function handleLogoUpload(input) {
    if (input.files && input.files[0]) {
        await uploadFile(input.files[0], 'logo');
    }
}

async function handleBackgroundUpload(input) {
    if (input.files && input.files[0]) {
        await uploadFile(input.files[0], 'background');
    }
}

async function uploadFile(file, type) {
    const formData = new FormData();
    formData.append(type, file);

    const progressId = type + 'Progress';
    const progressBar = document.querySelector('#' + progressId + ' .bg-blue-600');
    const progressText = document.querySelector('#' + progressId + ' p');

    // Show progress bar
    document.getElementById(progressId).classList.remove('hidden');
    progressBar.style.width = '0%';
    progressText.textContent = `Subiendo ${type === 'logo' ? 'logo' : 'imagen de fondo'}...`;

    try {
        const xhr = new XMLHttpRequest();

        // Progress tracking
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        });

        // Handle completion
        xhr.addEventListener('load', function() {
            document.getElementById(progressId).classList.add('hidden');

            if (xhr.status === 200) {
                const result = JSON.parse(xhr.responseText);
                if (result.success) {
                    showNotification(result.message, 'success');
                    location.reload(); // Reload to show updated images
                } else {
                    showNotification(result.message || 'Error al subir el archivo', 'error');
                }
            } else {
                const result = JSON.parse(xhr.responseText);
                showNotification(result.message || 'Error al subir el archivo', 'error');
            }
        });

        // Handle errors
        xhr.addEventListener('error', function() {
            document.getElementById(progressId).classList.add('hidden');
            showNotification('Error de conexión. Intente nuevamente.', 'error');
        });

        // Send request
        const url = type === 'logo' ?
            '{{ route("tenant.settings.upload.logo") }}' :
            '{{ route("tenant.settings.upload.background") }}';

        xhr.open('POST', url, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.send(formData);

    } catch (error) {
        document.getElementById(progressId).classList.add('hidden');
        showNotification('Error al procesar el archivo', 'error');
        console.error('Upload error:', error);
    }
}

// Delete image
async function deleteImage(type) {
    if (!confirm('¿Está seguro de que desea eliminar esta imagen?')) {
        return;
    }

    try {
        const response = await fetch(`{{ route("tenant.settings.delete.image") }}`.replace('{type}', type), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            location.reload(); // Reload to reflect changes
        } else {
            showNotification(result.message || 'Error al eliminar la imagen', 'error');
        }
    } catch (error) {
        showNotification('Error de conexión. Intente nuevamente.', 'error');
        console.error('Delete error:', error);
    }
}

// Drag and drop functionality
function setupDragAndDrop(dropZoneId, fileInputId, type) {
    const dropZone = document.getElementById(dropZoneId);

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('drag-active');
    }

    function unhighlight() {
        dropZone.classList.remove('drag-active');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            uploadFile(files[0], type);
        }
    }
}

// Initialize drag and drop
setupDragAndDrop('logoDropZone', 'logo', 'logo');
setupDragAndDrop('backgroundDropZone', 'background', 'background');

// Notification system
function showNotification(message, type) {
    const notification = document.getElementById('notification');

    notification.className = `fixed bottom-4 right-4 transform transition-transform duration-300 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;

    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ?
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' :
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;

    // Show notification
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Hide after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
    }, 3000);
}
</script>
@endpush