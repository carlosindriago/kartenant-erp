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

use App\Models\SecurityQuestion;
use App\Models\UserSecurityAnswer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SetupSecurityQuestions extends SimplePage
{
    protected static string $view = 'filament.app.pages.auth.setup-security-questions';

    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public function mount(): void
    {
        $user = Auth::user();

        // Si ya tiene preguntas de seguridad, redirigir al dashboard
        if ($user->hasSecurityQuestions()) {
            $this->redirect('/app');

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $questions = SecurityQuestion::getActiveQuestions();
        $questionOptions = $questions->pluck('question', 'id')->toArray();

        return $form
            ->schema([
                Select::make('question_1')
                    ->label('Primera Pregunta de Seguridad')
                    ->options($questionOptions)
                    ->required()
                    ->searchable(),

                TextInput::make('answer_1')
                    ->label('Respuesta 1')
                    ->required()
                    ->helperText('Escribe tu respuesta exactamente como la recordarás'),

                Select::make('question_2')
                    ->label('Segunda Pregunta de Seguridad')
                    ->options($questionOptions)
                    ->required()
                    ->searchable()
                    ->different('question_1')
                    ->helperText('Debe ser diferente a la primera pregunta'),

                TextInput::make('answer_2')
                    ->label('Respuesta 2')
                    ->required()
                    ->helperText('Escribe tu respuesta exactamente como la recordarás'),

                Select::make('question_3')
                    ->label('Tercera Pregunta de Seguridad')
                    ->options($questionOptions)
                    ->required()
                    ->searchable()
                    ->different('question_1')
                    ->different('question_2')
                    ->helperText('Debe ser diferente a las preguntas anteriores'),

                TextInput::make('answer_3')
                    ->label('Respuesta 3')
                    ->required()
                    ->helperText('Escribe tu respuesta exactamente como la recordarás'),
            ])
            ->statePath('data');
    }

    public function saveSecurityQuestions(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Validar que las preguntas sean diferentes
        $questions = [$data['question_1'], $data['question_2'], $data['question_3']];
        if (count(array_unique($questions)) !== 3) {
            Notification::make()
                ->title('Error de validación')
                ->body('Las tres preguntas deben ser diferentes.')
                ->danger()
                ->send();

            return;
        }

        try {
            // Guardar las preguntas de seguridad
            for ($i = 1; $i <= 3; $i++) {
                UserSecurityAnswer::create([
                    'user_id' => $user->id,
                    'security_question_id' => $data["question_{$i}"],
                    'answer_hash' => Hash::make(strtolower(trim($data["answer_{$i}"]))),
                ]);
            }

            Notification::make()
                ->title('Preguntas configuradas')
                ->body('Tus preguntas de seguridad han sido configuradas exitosamente.')
                ->success()
                ->send();

            // Redirigir al dashboard
            $this->redirect('/app');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body('Ocurrió un error al guardar las preguntas. Intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Configurar Preguntas de Seguridad')
                ->submit('saveSecurityQuestions')
                ->color('primary'),
        ];
    }

    public function getTitle(): string
    {
        return 'Configurar Preguntas de Seguridad';
    }

    public function getHeading(): string
    {
        return 'Configurar Preguntas de Seguridad';
    }

    public function getSubheading(): string
    {
        return 'Configura 3 preguntas de seguridad para poder recuperar tu contraseña en el futuro';
    }

    public static function registerNavigationItems(): array
    {
        return [];
    }
}
