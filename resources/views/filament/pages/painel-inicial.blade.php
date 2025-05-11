@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        body, .bg-gray-100, .min-h-screen, .filament-main, .filament-panels-page, .filament-panels-page *, .filament-main *, .filament-panels-page .bg-white {
            background: #18181b !important;
            color: #f4f4f5 !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
        }
        .border, .border-gray-100, .border-gray-200, .border-gray-300, .border-gray-400, .border-gray-500, .border-gray-600, .border-gray-700, .border-gray-800, .border-gray-900 {
            border-color: #27272a !important;
        }
        .bg-white, .bg-gray-50, .bg-gray-100, .bg-gray-200, .bg-gray-300, .bg-gray-400, .bg-gray-500, .bg-gray-600, .bg-gray-700, .bg-gray-800, .bg-gray-900 {
            background: #18181b !important;
        }
        .text-gray-900, .text-gray-800, .text-gray-700, .text-gray-600, .text-gray-500, .text-gray-400, .text-gray-300, .text-gray-200, .text-gray-100 {
            color: #f4f4f5 !important;
        }
        input, textarea, select, .ts-control, .ts-dropdown, .ts-wrapper, .filament-input, .filament-select {
            background: #18181b !important;
            color: #f4f4f5 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
        }
        .ts-dropdown {
            background: #23232b !important;
            color: #f4f4f5 !important;
        }
        .ts-dropdown .option:not(.active):hover {
            background: #33334d !important;
            color: #f4f4f5 !important;
        }
        .ts-dropdown .active, .ts-dropdown .option.active, .ts-dropdown .option:focus, .ts-dropdown .option:hover {
            background: #a5c9fa !important;
            color: #18181b !important;
            text-shadow: none !important;
        }
        .ts-control .item, .tomselect-dark .ts-control .item {
            background: #18181b !important;
            color: var(--primary-500) !important;
        }
        label {
            color: #f4f4f5 !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em !important;
        }
        button, .filament-button, .filament-panels-page button {
            background: #23232b !important;
            color: #f4f4f5 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
        }
        .ts-wrapper, .ts-wrapper:focus, .ts-wrapper.focus,
        .ts-control, .ts-control:focus, .ts-control.focus {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }
        .ts-wrapper {
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
        }
        .ts-control {
            border: none !important;
            border-radius: 0.5rem !important;
        }
        .ts-wrapper.focus, .ts-wrapper:focus {
            border: 1.5px solid #3b82f6 !important;
            border-radius: 0.5rem !important;
            outline: none !important;
            box-shadow: 0 0 0 2px #3b82f6 !important;
        }
        .ts-control input {
            color: #f4f4f5 !important;
            background: #18181b !important;
        }
        .ts-dropdown {
            background: #23232b !important;
            color: #f4f4f5 !important;
            border-radius: 0.5rem !important;
            border: 1px solid #27272a !important;
        }
        .ts-dropdown .active {
            background: #f59e42 !important;
            color: #18181b !important;
            text-shadow: none !important;
        }
        .ts-dropdown .option, .tomselect-dark .ts-dropdown .option {
            padding: 0.4rem 1rem !important;
            font-size: 1rem !important;
            text-shadow: none !important;
        }
        .ts-dropdown .option:not(.active):hover {
            background: #33334d !important;
            color: #f4f4f5 !important;
            text-shadow: none !important;
        }
        .ts-control .item {
            background: #18181b !important;
            color: var(--primary-500) !important;
            border-radius: 0.375rem !important;
            text-shadow: none !important;
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            display: flex !important;
            align-items: center !important;
        }
        .ts-wrapper.single .ts-control:after {
            border-color: #f59e42 transparent transparent transparent !important;
        }
        .tomselect-dark.ts-wrapper,
        .tomselect-dark.ts-wrapper *,
        .tomselect-dark .ts-control,
        .tomselect-dark .ts-control *,
        .tomselect-dark .ts-control input,
        .tomselect-dark .ts-dropdown,
        .tomselect-dark .ts-dropdown *,
        .tomselect-dark .ts-dropdown .option,
        .tomselect-dark .ts-dropdown .active,
        .tomselect-dark .ts-control .item {
            background: #18181b !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }
        .tomselect-dark .ts-dropdown {
            background: #23232b !important;
        }
        .tomselect-dark .ts-dropdown .active, .tomselect-dark .ts-dropdown .option.active, .tomselect-dark .ts-dropdown .option:focus, .tomselect-dark .ts-dropdown .option:hover {
            background: #a5c9fa !important;
            color: #18181b !important;
        }
        .tomselect-dark .ts-control .item {
            color: var(--primary-500) !important;
        }
        .tomselect-dark .ts-dropdown .option:not(.active):hover {
            background: #33334d !important;
            color: #f4f4f5 !important;
        }
        .tomselect-dark .ts-wrapper.single .ts-control:after {
            border-color: #f59e42 transparent transparent transparent !important;
        }
        label {
            color: #f4f4f5 !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em !important;
        }
        /* Forçar tema escuro também no <select> original */
        select#cliente, select#projeto, select#servico {
            background: #18181b !important;
            color: #f4f4f5 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
            font-size: 1rem !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
            min-height: 44px !important;
        }
        select, option {
            background: #18181b !important;
            color: #f4f4f5 !important;
            border: 1px solid #27272a !important;
            border-radius: 0.5rem !important;
            font-size: 1rem !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
            min-height: 44px !important;
        }
        select:focus {
            border: 1.5px solid #3b82f6 !important;
            border-radius: 0.5rem !important;
            outline: none !important;
            box-shadow: 0 0 0 2px #3b82f6 !important;
        }
    </style>
@endpush

<x-filament-panels::page>
    <div class="mb-6">
        @livewire('selecionar-diretorio-button')
    </div>
    
    {{-- Sistema antigo removido --}}
</x-filament-panels::page>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        function forceTomSelectDarkStyles(wrapper, selectEl) {
            if (selectEl) {
                selectEl.style.setProperty('background', '#18181b', 'important');
                selectEl.style.setProperty('color', '#f4f4f5', 'important');
                selectEl.style.setProperty('border', '1px solid #27272a', 'important');
                selectEl.style.setProperty('border-radius', '0.5rem', 'important');
                selectEl.style.setProperty('font-size', '1rem', 'important');
                selectEl.style.setProperty('font-family', "'Inter', 'Roboto', Arial, sans-serif", 'important');
                selectEl.style.setProperty('min-height', '44px', 'important');
            }
            if (!wrapper) return;
            wrapper.style.setProperty('background', '#18181b', 'important');
            wrapper.style.setProperty('color', '#f4f4f5', 'important');
            wrapper.style.setProperty('border-color', '#27272a', 'important');
            let control = wrapper.querySelector('.ts-control');
            if (control) {
                control.style.setProperty('background', '#18181b', 'important');
                control.style.setProperty('color', '#f4f4f5', 'important');
            }
            let input = wrapper.querySelector('.ts-control input');
            if (input) {
                input.style.setProperty('background', '#18181b', 'important');
                input.style.setProperty('color', '#f4f4f5', 'important');
            }
            let dropdown = wrapper.querySelector('.ts-dropdown');
            if (dropdown) {
                dropdown.style.setProperty('background', '#23232b', 'important');
                dropdown.style.setProperty('color', '#f4f4f5', 'important');
            }
        }
        function initTomSelects() {
            setTimeout(function() {
                if (window.TomSelect) {
                    ["#cliente", "#projeto", "#servico"].forEach(function(id) {
                        let el = document.querySelector(id);
                        forceTomSelectDarkStyles(null, el);
                        if (el && el.tomselect) {
                            el.tomselect.destroy();
                        }
                        if (el) {
                            const tom = new TomSelect(el, {
                                create: false,
                                maxItems: 1,
                                allowEmptyOption: true,
                                searchField: ['text'],
                                controlInput: false,
                                render: {
                                    option: function(data, escape) {
                                        return `<div>${escape(data.text)}</div>`;
                                    }
                                },
                                onInitialize: function() {
                                    this.wrapper.classList.add('tomselect-dark');
                                    forceTomSelectDarkStyles(this.wrapper, el);
                                },
                                onDropdownOpen: function() {
                                    forceTomSelectDarkStyles(this.wrapper, el);
                                },
                                onChange: function(value) {
                                    el.dispatchEvent(new Event('input', { bubbles: true }));
                                    el.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            });
                            const observer = new MutationObserver(function() {
                                if (!tom.wrapper.classList.contains('tomselect-dark')) {
                                    tom.wrapper.classList.add('tomselect-dark');
                                }
                                forceTomSelectDarkStyles(tom.wrapper, el);
                            });
                            observer.observe(tom.wrapper, { attributes: true, childList: true, subtree: true });
                        }
                    });
                }
            }, 50);
        }
        document.addEventListener('livewire:navigated', initTomSelects);
        document.addEventListener('DOMContentLoaded', initTomSelects);
        document.addEventListener('livewire:load', initTomSelects);
        document.addEventListener('livewire:update', initTomSelects);
    </script>
@endpush