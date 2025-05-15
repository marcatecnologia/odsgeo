<div>
    <textarea id="bulk-copy-textarea" class="w-full p-2 border rounded dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700" rows="10" readonly>{{ $messages }}</textarea>
    <button
        type="button"
        class="mt-2 px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700"
        onclick="navigator.clipboard.writeText(document.getElementById('bulk-copy-textarea').value); if(window.Filament && Filament.Notifications) { Filament.Notifications.Notification.make().title('Mensagens copiadas!').success().send(); } else { alert('Mensagens copiadas!'); }"
    >Copiar para área de transferência</button>
</div> 