@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-wrapper, .ts-wrapper:focus, .ts-wrapper.focus,
        .ts-control, .ts-control:focus, .ts-control.focus {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }
        .ts-wrapper {
            background: #18181b !important;
            border-radius: 0.5rem !important;
            border: 1px solid #27272a !important;
            font-family: 'Inter', 'Roboto', Arial, sans-serif !important;
        }
        .ts-control {
            background: #18181b !important;
            color: #f4f4f5 !important;
            min-height: 44px !important;
            font-size: 1rem !important;
            border-radius: 0.5rem !important;
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
        .ts-dropdown .option {
            padding: 0.75rem 1rem !important;
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
        label {
            color: #f4f4f5 !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em !important;
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
        .tomselect-dark .ts-dropdown .active {
            background: #f59e42 !important;
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
    </style>
@endpush

<x-filament-panels::page>
    @livewire('painel-seleca')
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
                        // Forçar o tema escuro no select original antes de inicializar o Tom Select
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
                            // MutationObserver para garantir a classe customizada
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