<div>
    <x-filament::button
        wire:click="$set('showModal', true)"
        icon="heroicon-o-arrow-up-tray"
    >
        Importar Coordenadas
    </x-filament::button>

    <x-filament::modal
        wire:model="showModal"
        width="md"
    >
        <x-slot name="header">
            Importar Coordenadas
        </x-slot>

        <x-slot name="content">
            <form wire:submit="import">
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-filament::input.file
                            wire:model="file"
                            label="Arquivo CSV/TXT"
                            accept=".csv,.txt"
                        />
                        <x-filament::input.error :messages="$errors->get('file')" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.radio
                            wire:model="format"
                            label="Formato de Entrada"
                            :options="[
                                'utm' => 'UTM',
                                'decimal' => 'Geográfica Decimal',
                                'gms' => 'Geográfica GMS',
                            ]"
                        />
                        <x-filament::input.error :messages="$errors->get('format')" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model="datum"
                            label="Datum"
                            :options="[
                                'SIRGAS2000' => 'SIRGAS2000',
                                'SAD69' => 'SAD69',
                            ]"
                        />
                        <x-filament::input.error :messages="$errors->get('datum')" />
                    </x-filament::input.wrapper>

                    <div x-data="{ format: @entangle('format') }">
                        <template x-if="format === 'utm'">
                            <div class="space-y-4">
                                <x-filament::input.wrapper>
                                    <x-filament::input.text
                                        wire:model="utmZone"
                                        type="number"
                                        label="Zona UTM"
                                        min="18"
                                        max="25"
                                    />
                                    <x-filament::input.error :messages="$errors->get('utmZone')" />
                                </x-filament::input.wrapper>

                                <x-filament::input.wrapper>
                                    <x-filament::input.text
                                        wire:model="centralMeridian"
                                        label="Meridiano Central"
                                    />
                                    <x-filament::input.error :messages="$errors->get('centralMeridian')" />
                                </x-filament::input.wrapper>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-between items-center">
                        <x-filament::button
                            type="button"
                            wire:click="downloadTemplate"
                            color="secondary"
                        >
                            Baixar Template
                        </x-filament::button>

                        <x-filament::button
                            type="submit"
                            wire:loading.attr="disabled"
                        >
                            Importar
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </x-slot>
    </x-filament::modal>
</div> 