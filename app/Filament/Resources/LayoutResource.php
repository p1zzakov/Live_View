<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LayoutResource\Pages;
use App\Models\Layout;
use App\Models\Camera;
use App\Models\Nvr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LayoutResource extends Resource
{
    protected static ?string $model = Layout::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Раскладки';
    protected static ?string $modelLabel = 'Раскладка';
    protected static ?string $pluralModelLabel = 'Раскладки';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Select::make('grid_type')
                            ->label('Тип сетки')
                            ->options([
                                '1x1' => '1 камера (1x1)',
                                '2x2' => '4 камеры (2x2)',
                                '3x3' => '9 камер (3x3)',
                                '4x4' => '16 камер (4x4)',
                                '5x5' => '25 камер (5x5)',
                                '6x6' => '36 камер (6x6)',
                                '6x8' => '48 камер (6x8)',
                                '8x6' => '48 камер (8x6)',
                            ])
                            ->required()
                            ->default('2x2')
                            ->live(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Раскладка по умолчанию')
                            ->helperText('Будет загружаться автоматически'),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Публичная')
                            ->helperText('Доступна всем пользователям')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Камеры')
                    ->description('Фильтруйте по NVR и выбирайте камеры для раскладки')
                    ->schema([
                        Forms\Components\Select::make('nvr_filter')
                            ->label('Фильтр по NVR')
                            ->options(fn () => ['' => 'Все NVR'] + Nvr::where('is_active', true)->pluck('name', 'id')->toArray())
                            ->default('')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn () => null),

                        Forms\Components\CheckboxList::make('camera_ids')
                            ->label('Выберите камеры')
                            ->options(function (Forms\Get $get) {
                                $nvrId = $get('nvr_filter');
                                $query = Camera::where('is_active', true)->with('nvr');
                                if ($nvrId) {
                                    $query->where('nvr_id', $nvrId);
                                }
                                return $query->get()->mapWithKeys(function ($camera) {
                                    $nvrName = $camera->nvr ? $camera->nvr->name : 'Без NVR';
                                    return [$camera->id => "[{$nvrName}] Ch{$camera->channel_number} - {$camera->name}"];
                                });
                            })
                            ->searchable()
                            ->columns(3)
                            ->gridDirection('row')
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('grid_type')
                    ->label('Сетка')
                    ->colors([
                        'secondary' => '1x1',
                        'success' => '2x2',
                        'warning' => '3x3',
                        'danger' => '4x4',
                        'primary' => '5x5',
                        'info' => '6x6',
                        'gray' => fn ($state) => in_array($state, ['6x8', '8x6']),
                    ]),

                Tables\Columns\TextColumn::make('cameras_count')
                    ->label('Камер')
                    ->counts('cameras')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Публичная')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grid_type')
                    ->label('Тип сетки')
                    ->options([
                        '1x1' => '1x1',
                        '2x2' => '2x2',
                        '3x3' => '3x3',
                        '4x4' => '4x4',
                        '5x5' => '5x5',
                        '6x6' => '6x6',
                        '6x8' => '6x8',
                        '8x6' => '8x6',
                    ]),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('По умолчанию'),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Публичные'),
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
            'index' => Pages\ListLayouts::route('/'),
            'create' => Pages\CreateLayout::route('/create'),
            'edit' => Pages\EditLayout::route('/{record}/edit'),
        ];
    }
}