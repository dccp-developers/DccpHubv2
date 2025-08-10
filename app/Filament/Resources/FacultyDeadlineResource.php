<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FacultyDeadlineResource\Pages;
use App\Models\FacultyDeadline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FacultyDeadlineResource extends Resource
{
    protected static ?string $model = FacultyDeadline::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Faculty Management';

    protected static ?string $navigationLabel = 'Faculty Deadlines';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\DateTimePicker::make('due_date')->required(),
            Forms\Components\Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->required()->default('medium'),
            Forms\Components\TextInput::make('type')->maxLength(100),
            Forms\Components\Select::make('faculty_id')
                ->relationship('faculty', 'id')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('class_id')
                ->relationship('class', 'subject_code')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->subject_code . ' - Sec ' . $record->section)
                ->searchable(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('due_date')->dateTime('M j, Y g:i A')->sortable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors(['success' => 'low', 'warning' => 'medium', 'danger' => 'high'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('faculty_id')->label('Faculty'),
                Tables\Columns\TextColumn::make('class.subject_code')->label('Class')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('priority')->options(['low'=>'Low','medium'=>'Medium','high'=>'High']),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacultyDeadlines::route('/'),
            'create' => Pages\CreateFacultyDeadline::route('/create'),
            'edit' => Pages\EditFacultyDeadline::route('/{record}/edit'),
        ];
    }
}

