<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MissingStudentRequestResource\Pages;
use App\Models\MissingStudentRequest;
use App\Models\FacultyNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MissingStudentRequestResource extends Resource
{
    protected static ?string $model = MissingStudentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Missing Student Requests';

    protected static ?string $modelLabel = 'Missing Student Request';

    protected static ?string $pluralModelLabel = 'Missing Student Requests';

    protected static ?string $navigationGroup = 'Faculty Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Student Full Name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\TextInput::make('student_id')
                            ->label('Student ID')
                            ->maxLength(50)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes from Faculty')
                            ->rows(3)
                            ->maxLength(1000)
                            ->disabled(fn ($context) => $context === 'view')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Class & Faculty Information')
                    ->schema([
                        Forms\Components\Select::make('class_id')
                            ->label('Class')
                            ->relationship('class', 'subject_code')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->subject_code} - {$record->subject_title} (Sec {$record->section})")
                            ->disabled()
                            ->required(),

                        Forms\Components\Select::make('faculty_id')
                            ->label('Faculty')
                            ->relationship('faculty', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->disabled()
                            ->required(),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->disabled()
                            ->displayFormat('M j, Y g:i A'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Admin Processing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->default('pending')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (in_array($state, ['approved', 'rejected'])) {
                                    $set('processed_at', now());
                                    $set('processed_by', Auth::id());
                                }
                            }),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At')
                            ->disabled()
                            ->displayFormat('M j, Y g:i A')
                            ->visible(fn ($get) => in_array($get('status'), ['approved', 'rejected'])),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Add notes about how this request was resolved or why it was rejected.')
                            ->visible(fn ($get) => in_array($get('status'), ['approved', 'rejected']))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->placeholder('Not provided'),

                Tables\Columns\TextColumn::make('class.subject_code')
                    ->label('Class')
                    ->formatStateUsing(fn ($record) => "{$record->class->subject_code} - Sec {$record->class->section}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('faculty.first_name')
                    ->label('Faculty')
                    ->formatStateUsing(fn ($record) => "{$record->faculty->first_name} {$record->faculty->last_name}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not processed'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Not provided')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Faculty Notes')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),

                Tables\Filters\Filter::make('submitted_today')
                    ->label('Submitted Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('submitted_at', today())),

                Tables\Filters\Filter::make('submitted_this_week')
                    ->label('Submitted This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('submitted_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Missing Student Request')
                    ->modalDescription('This will mark the request as approved and notify the faculty member.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('Optional: Add notes about how this was resolved...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'processed_at' => now(),
                            'processed_by' => Auth::id(),
                            'admin_notes' => $data['admin_notes'] ?? null,
                        ]);

                        // Send notification to faculty
                        static::sendFacultyNotification($record, 'approved');

                        Notification::make()
                            ->title('Request Approved')
                            ->body('The missing student request has been approved and the faculty has been notified.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Missing Student Request')
                    ->modalDescription('This will mark the request as rejected and notify the faculty member.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Reason for Rejection')
                            ->placeholder('Please explain why this request is being rejected...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'processed_at' => now(),
                            'processed_by' => Auth::id(),
                            'admin_notes' => $data['admin_notes'],
                        ]);

                        // Send notification to faculty
                        static::sendFacultyNotification($record, 'rejected');

                        Notification::make()
                            ->title('Request Rejected')
                            ->body('The missing student request has been rejected and the faculty has been notified.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Requests')
                        ->modalDescription('This will approve all selected pending requests and notify the faculty members.')
                        ->action(function (Collection $records) {
                            $pendingRecords = $records->where('status', 'pending');

                            foreach ($pendingRecords as $record) {
                                $record->update([
                                    'status' => 'approved',
                                    'processed_at' => now(),
                                    'processed_by' => Auth::id(),
                                ]);

                                static::sendFacultyNotification($record, 'approved');
                            }

                            Notification::make()
                                ->title('Requests Approved')
                                ->body("{$pendingRecords->count()} requests have been approved and faculty have been notified.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissingStudentRequests::route('/'),
            'create' => Pages\CreateMissingStudentRequest::route('/create'),
            'edit' => Pages\EditMissingStudentRequest::route('/{record}/edit'),
        ];
    }

    /**
     * Send notification to faculty about request status change
     */
    protected static function sendFacultyNotification(MissingStudentRequest $request, string $status): void
    {
        try {
            $title = $status === 'approved'
                ? 'Missing Student Request Approved'
                : 'Missing Student Request Rejected';

            $message = $status === 'approved'
                ? "Your request to add '{$request->full_name}' to {$request->class->subject_code} has been approved. The student should now appear in your class roster."
                : "Your request to add '{$request->full_name}' to {$request->class->subject_code} has been rejected.";

            if ($request->admin_notes) {
                $message .= "\n\nAdmin Notes: " . $request->admin_notes;
            }

            FacultyNotification::create([
                'faculty_id' => $request->faculty_id,
                'title' => $title,
                'message' => $message,
                'type' => $status === 'approved' ? 'success' : 'warning',
                'is_read' => false,
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send faculty notification for missing student request', [
                'request_id' => $request->id,
                'faculty_id' => $request->faculty_id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the navigation badge count (pending requests)
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Set navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
