<?php

namespace App\Filament\Support;

use App\Filament\Support\Pages\EditDocument;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class DocumentForm
{
    /**
     * Wrap the document form: show the number and status once the document exists, and lock it once it leaves draft.
     *
     * @param  array<int, mixed>  $header
     * @param  array<int, mixed>  $lines
     */
    public static function configure(Schema $schema, string $numberAttribute, array $header, array $lines): Schema
    {
        return $schema
            ->disabled(fn (Component $livewire): bool => $livewire instanceof EditDocument && ! $livewire->isEditable())
            ->components([
                Section::make(__('Document'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make($numberAttribute)
                            ->label(__('Document no'))
                            ->hiddenOn('create'),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (BackedEnum $state): string => __("status.{$state->value}"))
                            ->hiddenOn('create'),
                        ...$header,
                    ]),
                Section::make(__('Lines'))
                    ->columnSpanFull()
                    ->schema($lines)
                    ->visible($lines !== []),
            ]);
    }

    /**
     * Keep only the given fields of every line, dropping read-only fields shown in the repeater.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function editableLines(array $data, array $fields): array
    {
        if (array_key_exists('items', $data)) {
            $data['items'] = array_values(array_map(fn (array $line) => array_intersect_key($line, array_flip($fields)), $data['items']));
        }

        return $data;
    }
}
