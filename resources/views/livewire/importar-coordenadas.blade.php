<div>
    <form wire:submit="import">
        <div class="space-y-4">
            <div class="filament-forms-field-wrapper">
                <x-filament::file-upload
                    wire:model="file"
                    label="Arquivo CSV/TXT"
                    accept=".csv,.txt"
                    directory="coordenadas"
                />
                <x-filament::input.error :messages="$errors->get('file')" />
            </div>

            <div class="filament-forms-field-wrapper">
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
            </div>

            <div class="filament-forms-field-wrapper">
                <x-filament::input.select
                    wire:model="datum"
                    label="Datum"
                    :options="[
                        'SIRGAS2000' => 'SIRGAS2000',
                        'SAD69' => 'SAD69',
                    ]"
                />
                <x-filament::input.error :messages="$errors->get('datum')" />
            </div>

            <div x-data="{ format: @entangle('format') }">
                <template x-if="format === 'utm'">
                    <div class="space-y-4">
                        <div class="filament-forms-field-wrapper">
                            <x-filament::input.text
                                wire:model="utmZone"
                                type="number"
                                label="Zona UTM"
                                min="18"
                                max="25"
                            />
                            <x-filament::input.error :messages="$errors->get('utmZone')" />
                        </div>

                        <div class="filament-forms-field-wrapper">
                            <x-filament::input.text
                                wire:model="centralMeridian"
                                label="Meridiano Central"
                            />
                            <x-filament::input.error :messages="$errors->get('centralMeridian')" />
                        </div>
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
</div> 