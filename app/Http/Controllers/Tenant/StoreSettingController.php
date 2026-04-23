<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteImageRequest;
use App\Http\Requests\UploadBackgroundRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Models\StoreSetting;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    private ImageUploadService $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Display the store settings form.
     */
    public function index(): View
    {
        $settings = StoreSetting::current();
        $storageUsage = $this->imageUploadService->getStorageUsage(tenant());

        return view('tenant.settings.edit', compact('settings', 'storageUsage'));
    }

    /**
     * Update basic settings (colors, messages, etc.).
     */
    public function updateBasic(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'store_name' => 'required|string|max:255',
                'store_slogan' => 'nullable|string|max:255',
                'brand_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'welcome_message' => 'nullable|string|max:1000',
                'primary_font' => 'nullable|string|in:Inter,Roboto,Open Sans,Poppins,Montserrat',
                'is_active' => 'boolean',
                'show_background_image' => 'boolean',
                'facebook_url' => 'nullable|url|max:500',
                'instagram_url' => 'nullable|url|max:500',
                'whatsapp_number' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email|max:255',
            ], [
                'store_name.required' => 'El nombre de la tienda es obligatorio.',
                'store_name.max' => 'El nombre de la tienda no puede exceder 255 caracteres.',
                'brand_color.regex' => 'El color debe estar en formato hexadecimal (#FF5733).',
                'welcome_message.max' => 'El mensaje de bienvenida no puede exceder 1000 caracteres.',
                'facebook_url.url' => 'La URL de Facebook no es válida.',
                'instagram_url.url' => 'La URL de Instagram no es válida.',
                'contact_email.email' => 'El correo electrónico no es válido.',
            ]);

            $settings = StoreSetting::current();
            $settings->update($validated);

            Log::info('Store settings updated', [
                'tenant_id' => tenant()->id,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente.',
                'settings' => $settings->fresh(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hay errores en el formulario.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error updating store settings', [
                'tenant_id' => tenant()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración. Intente nuevamente.',
            ], 500);
        }
    }

    /**
     * Upload logo image.
     */
    public function uploadLogo(UploadLogoRequest $request): JsonResponse
    {
        try {
            $file = $request->file('logo');
            $settings = StoreSetting::current();

            $logoPath = $this->imageUploadService->uploadLogo($file, tenant());
            $settings->updateLogo($logoPath);

            Log::info('Logo uploaded successfully', [
                'tenant_id' => tenant()->id,
                'path' => $logoPath,
                'original_name' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo subido correctamente.',
                'logo_url' => $settings->getLogoPublicUrl(),
                'logo_size' => $settings->getLogoSize(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading logo', [
                'tenant_id' => tenant()->id,
                'error' => $e->getMessage(),
                'file' => $request->file('logo')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error al subir el logo. Intente con otro archivo.',
            ], 422);
        }
    }

    /**
     * Upload background image.
     */
    public function uploadBackground(UploadBackgroundRequest $request): JsonResponse
    {
        try {
            $file = $request->file('background');
            $settings = StoreSetting::current();

            $backgroundPath = $this->imageUploadService->uploadBackground($file, tenant());
            $settings->updateBackground($backgroundPath);

            Log::info('Background uploaded successfully', [
                'tenant_id' => tenant()->id,
                'path' => $backgroundPath,
                'original_name' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen de fondo subida correctamente.',
                'background_url' => $settings->getBackgroundPublicUrl(),
                'background_size' => $settings->getBackgroundSize(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading background', [
                'tenant_id' => tenant()->id,
                'error' => $e->getMessage(),
                'file' => $request->file('background')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error al subir la imagen de fondo. Intente con otro archivo.',
            ], 422);
        }
    }

    /**
     * Delete image (logo or background).
     */
    public function deleteImage(DeleteImageRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            $settings = StoreSetting::current();

            if ($type === 'logo') {
                $oldPath = $settings->logo_path;
                $settings->deleteLogo();
                $message = 'Logo eliminado correctamente.';
            } else {
                $oldPath = $settings->background_image_path;
                $settings->deleteBackground();
                $message = 'Imagen de fondo eliminada correctamente.';
            }

            Log::info('Image deleted successfully', [
                'tenant_id' => tenant()->id,
                'type' => $type,
                'path' => $oldPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting image', [
                'tenant_id' => tenant()->id,
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen. Intente nuevamente.',
            ], 500);
        }
    }

    /**
     * Preview store settings in a new tab.
     */
    public function preview(): View
    {
        $settings = StoreSetting::current();

        return view('tenant.settings.preview', compact('settings'));
    }

    /**
     * Get storage usage statistics.
     */
    public function storageStats(): JsonResponse
    {
        try {
            $stats = $this->imageUploadService->getStorageUsage(tenant());

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting storage stats', [
                'tenant_id' => tenant()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de almacenamiento.',
            ], 500);
        }
    }

    /**
     * Get current settings as JSON (for AJAX updates).
     */
    public function getCurrent(): JsonResponse
    {
        try {
            $settings = StoreSetting::current();

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $settings->id,
                    'store_name' => $settings->store_name,
                    'store_slogan' => $settings->store_slogan,
                    'brand_color' => $settings->brand_color,
                    'welcome_message' => $settings->welcome_message,
                    'primary_font' => $settings->primary_font,
                    'is_active' => $settings->is_active,
                    'show_background_image' => $settings->show_background_image,
                    'facebook_url' => $settings->facebook_url,
                    'instagram_url' => $settings->instagram_url,
                    'whatsapp_number' => $settings->whatsapp_number,
                    'contact_email' => $settings->contact_email,
                    'logo_url' => $settings->getLogoPublicUrl(),
                    'background_url' => $settings->getBackgroundPublicUrl(),
                    'has_logo' => $settings->hasLogo(),
                    'has_background' => $settings->hasBackground(),
                    'logo_size' => $settings->getLogoSize(),
                    'background_size' => $settings->getBackgroundSize(),
                    'whatsapp_url' => $settings->whatsapp_url,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting current settings', [
                'tenant_id' => tenant()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración actual.',
            ], 500);
        }
    }
}
