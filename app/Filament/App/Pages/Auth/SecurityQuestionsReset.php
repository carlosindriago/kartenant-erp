<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\UserSecurityAnswer;
use Illuminate\Support\Facades\Hash;

class SecurityQuestionsReset extends SimplePage
{
    protected static string $view = 'filament.app.pages.auth.security-questions-reset';

    public ?array $data = [];
    public $user;
    public $securityQuestions;

    public function mount(): void
    {
        // Verificar que el código fue verificado
        if (!session('security_code_verified') || !session('password_reset_email')) {
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));
            return;
        }

        // Obtener el usuario y sus preguntas de seguridad
        $this->user = User::where('email', session('password_reset_email'))->first();
        
        if (!$this->user) {
            session()->forget(['password_reset_code', 'password_reset_email', 'password_reset_expires', 'security_code_verified']);
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));
            return;
        }

        $this->securityQuestions = UserSecurityAnswer::with('securityQuestion')
            ->where('user_id', $this->user->id)
            ->get();

        if ($this->securityQuestions->isEmpty()) {
            Notification::make()
                ->title('Sin preguntas de seguridad')
                ->body('Este usuario no tiene preguntas de seguridad configuradas. Contacta al administrador.')
                ->warning()
                ->send();
                
            $this->redirect(route('filament.app.auth.login', ['tenant' => Filament::getTenant()]));
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $formSchema = [];

        foreach ($this->securityQuestions as $index => $userAnswer) {
            $formSchema[] = TextInput::make("answer_{$userAnswer->id}")
                ->label($userAnswer->securityQuestion->question)
                ->required()
                ->placeholder('Ingresa tu respuesta');
        }

        return $form
            ->schema($formSchema)
            ->statePath('data');
    }

    public function verifyAnswers(): void
    {
        $data = $this->form->getState();
        
        $allCorrect = true;
        
        foreach ($this->securityQuestions as $userSecurityAnswer) {
            $userAnswer = $data["answer_{$userSecurityAnswer->id}"] ?? '';
            
            if (!Hash::check(strtolower(trim($userAnswer)), $userSecurityAnswer->answer_hash)) {
                $allCorrect = false;
                break;
            }
        }

        if (!$allCorrect) {
            Notification::make()
                ->title('Respuestas incorrectas')
                ->body('Una o más respuestas son incorrectas. Verifica e intenta nuevamente.')
                ->danger()
                ->send();
            return;
        }

        // Respuestas correctas, marcar como verificado y redirigir al reset de contraseña
        session(['security_questions_verified' => true]);
        
        Notification::make()
            ->title('Verificación exitosa')
            ->body('Respuestas correctas. Ahora puedes establecer tu nueva contraseña.')
            ->success()
            ->send();

        $this->redirect(route('tenant.reset-password', ['tenant' => Filament::getTenant()]));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verificar Respuestas')
                ->submit('verifyAnswers')
                ->color('primary'),
            Action::make('back')
                ->label('Volver')
                ->url(fn () => route('tenant.verify-security-code', ['tenant' => Filament::getTenant()]))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Preguntas de Seguridad';
    }

    public function getHeading(): string
    {
        return 'Preguntas de Seguridad';
    }

    public function getSubheading(): string
    {
        return 'Responde tus preguntas de seguridad para continuar';
    }
}
