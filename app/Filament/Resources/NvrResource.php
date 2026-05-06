<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NvrResource\Pages;
use App\Models\Nvr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NvrResource extends Resource
{
    protected static ?string $model = Nvr::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-server';
    
    protected static ?string $navigationLabel = 'NVR/DVR';
    
    protected static ?string $modelLabel = 'NVR';
    
    protected static ?string $pluralModelLabel = 'NVR/DVR';
    
    protected static ?int $navigationSort = 1;

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
                            
                        Forms\Components\Select::make('vendor')
                            ->label('Производитель')
                            ->options([
                                'polyvision' => 'Polyvision',
                                'dahua' => 'Dahua',
                                'hikvision' => 'Hikvision',
                                'other' => 'Другой',
                            ])
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Сетевые настройки')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP адрес')
                            ->required()
                            ->ip(),
                            
                        Forms\Components\TextInput::make('http_port')
                            ->label('HTTP порт')
                            ->numeric()
                            ->default(80),
                            
                        Forms\Components\TextInput::make('rtsp_port')
                            ->label('RTSP порт')
                            ->numeric()
                            ->default(554),
                            
                        Forms\Components\TextInput::make('api_endpoint')
                            ->label('API Endpoint')
                            ->maxLength(255)
                            ->placeholder('http://192.168.1.100/cgi-bin'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Учетные данные')
                    ->schema([
                        Forms\Components\TextInput::make('credentials.username')
                            ->label('Логин')
                            ->required(),
                            
                        Forms\Components\TextInput::make('credentials.password')
                            ->label('Пароль')
                            ->password()
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
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
                    
                Tables\Columns\BadgeColumn::make('vendor')
                    ->label('Производитель')
                    ->colors([
                        'primary' => 'polyvision',
                        'success' => 'dahua',
                        'warning' => 'hikvision',
                        'secondary' => 'other',
                    ]),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP адрес')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('cameras_count')
                    ->label('Камер')
                    ->counts('cameras')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('last_health_check')
                    ->label('Последняя проверка')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->label('Производитель')
                    ->options([
                        'polyvision' => 'Polyvision',
                        'dahua' => 'Dahua',
                        'hikvision' => 'Hikvision',
                        'other' => 'Другой',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
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
            'index' => Pages\ListNvrs::route('/'),
            'create' => Pages\CreateNvr::route('/create'),
            'edit' => Pages\EditNvr::route('/{record}/edit'),
        ];
    }
}