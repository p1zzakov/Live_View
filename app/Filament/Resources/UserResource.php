<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Layout;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $modelLabel = 'Пользователь';
    protected static ?string $pluralModelLabel = 'Пользователи';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email / Логин')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->hint('Оставьте пустым чтобы не менять'),
                    ])->columns(2),

                Forms\Components\Section::make('Права доступа')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                'admin' => '👑 Администратор',
                                'operator' => '👁 Оператор',
                            ])
                            ->required()
                            ->default('operator'),

                        Forms\Components\Select::make('layout_id')
                            ->label('Раскладка по умолчанию')
                            ->options(Layout::where('is_public', true)->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Не назначена'),

                        Forms\Components\Toggle::make('can_access_archive')
                            ->label('Доступ к архиву')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Разрешённые раскладки')
                    ->description('Если не выбрано ни одной — пользователь видит все публичные раскладки')
                    ->schema([
                        Forms\Components\CheckboxList::make('allowedLayouts')
                            ->label('')
                            ->relationship('allowedLayouts', 'name')
                            ->options(Layout::where('is_public', true)->get()->mapWithKeys(function ($layout) {
                                return [$layout->id => "{$layout->name} ({$layout->grid_type})"];
                            }))
                            ->columns(3)
                            ->gridDirection('row'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Роль')
                    ->colors([
                        'warning' => 'admin',
                        'success' => 'operator',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'admin' => '👑 Администратор',
                        'operator' => '👁 Оператор',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('layout.name')
                    ->label('Раскладка')
                    ->default('—'),

                Tables\Columns\TextColumn::make('allowedLayouts_count')
                    ->label('Разрешено раскладок')
                    ->counts('allowedLayouts')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Все' : $state),

                Tables\Columns\IconColumn::make('can_access_archive')
                    ->label('Архив')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        if ($record->id === auth()->id()) {
                            throw new \Exception('Нельзя удалить текущего пользователя!');
                        }
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}