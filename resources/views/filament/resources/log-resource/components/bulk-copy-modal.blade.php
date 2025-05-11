<div>
    <textarea id="bulk-copy-textarea" class="w-full p-2 border rounded" rows="10" readonly>{{ $messages }}</textarea>
    <button
        type="button"
        class="mt-2 px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
        onclick="navigator.clipboard.writeText(document.getElementById('bulk-copy-textarea').value); if(window.Filament && Filament.Notifications) { Filament.Notifications.Notification.make().title('Mensagens copiadas!').success().send(); } else { alert('Mensagens copiadas!'); }"
    >Copiar para área de transferência</button>
</div> 