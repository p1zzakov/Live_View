<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CameraResource\Pages;
use App\Models\Camera;
use App\Models\Nvr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CameraResource extends Resource
{
    protected static ?string $model = Camera::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Камеры';
    protected static ?string $modelLabel = 'Камера';
    protected static ?string $pluralModelLabel = 'Камеры';
    protected static ?int $navigationSort = 2;

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

                        Forms\Components\Select::make('nvr_id')
                            ->label('NVR/DVR')
                            ->options(Nvr::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                self::updateRtspUrl($state, $set)
                            ),

                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'dahua' => 'Dahua',
                                'hikvision' => 'Hikvision',
                                'polyvision' => 'Polyvision',
                                'analog' => 'Аналоговая',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('channel_number')
                            ->label('Номер канала')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(128)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, $get) =>
                                self::updateRtspUrl($get('nvr_id'), $set, $state)
                            ),

                        Forms\Components\TextInput::make('location')
                            ->label('Местоположение')
                            ->maxLength(255)
                            ->placeholder('Например: Главный вход'),
                    ])->columns(2),

                Forms\Components\Section::make('RTSP настройки')
                    ->schema([
                        Forms\Components\TextInput::make('rtsp_live_url')
                            ->label('RTSP URL (Live)')
                            ->required()
                            ->maxLength(500)
                            ->hint('Будет сгенерирован автоматически'),

                        Forms\Components\TextInput::make('rtsp_playback_template')
                            ->label('RTSP URL (Playback шаблон)')
                            ->maxLength(500)
                            ->placeholder('rtsp://...?starttime={START}&endtime={END}'),
                    ])->columns(1),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\TextInput::make('onvif_url')
                            ->label('ONVIF URL')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),

                        Forms\Components\Toggle::make('is_recording')
                            ->label('Запись включена')
                            ->default(false),
                    ])->columns(3),
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

                Tables\Columns\TextColumn::make('nvr.name')
                    ->label('NVR')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('channel_number')
                    ->label('Канал')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'dahua',
                        'warning' => 'hikvision',
                        'primary' => 'polyvision',
                        'secondary' => 'analog',
                    ]),

                Tables\Columns\TextColumn::make('location')
                    ->label('Местоположение')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_health_check')
                    ->label('Проверка')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nvr')
                    ->relationship('nvr', 'name')
                    ->label('NVR'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'dahua' => 'Dahua',
                        'hikvision' => 'Hikvision',
                        'polyvision' => 'Polyvision',
                        'analog' => 'Аналоговая',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->actions([
                Tables\Actions\Action::make('test_stream')
                    ->label('Тест')
                    ->icon('heroicon-o-play')
                    ->url(fn (Camera $record): string =>
                        "/viewer/?camera={$record->channel_number}&nvr={$record->nvr_id}"
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function updateRtspUrl($nvrId, callable $set, $channelNumber = null)
    {
        if (!$nvrId) return;

        $nvr = Nvr::find($nvrId);
        if (!$nvr || !$channelNumber) return;

        $username = $nvr->credentials['username'] ?? 'admin';
        $password = $nvr->credentials['password'] ?? '';
        $port = $nvr->rtsp_port ?? 554;

        $rtspUrl = match($nvr->vendor) {
            'dahua' => "rtsp://{$username}:{$password}@{$nvr->ip_address}:{$port}/cam/realmonitor?channel={$channelNumber}&subtype=1",
            'hikvision' => "rtsp://{$username}:{$password}@{$nvr->ip_address}:{$port}/Streaming/Channels/{$channelNumber}01",
            'polyvision' => "rtsp://{$nvr->ip_address}:{$port}/user={$username}&password={$password}&channel={$channelNumber}&stream=1.sdp",
            default => "rtsp://{$username}:{$password}@{$nvr->ip_address}:{$port}/channel{$channelNumber}",
        };

        $set('rtsp_live_url', $rtspUrl);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCameras::route('/'),
            'create' => Pages\CreateCamera::route('/create'),
            'edit' => Pages\EditCamera::route('/{record}/edit'),
        ];
    }
}