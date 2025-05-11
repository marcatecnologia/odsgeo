@php
    $messageId = 'message-' . uniqid();
@endphp

<span title="{{ $message }}" class="inline-flex items-center gap-2">
    <span class="truncate max-w-[300px] align-middle">{{ mb_strimwidth($message, 0, 100, '...') }}</span>
</span>

<textarea id="{{ $messageId }}" style="display: none;">{{ $message }}</textarea>

<script>
function copyMessage(messageId) {
    const textarea = document.getElementById(messageId);
    textarea.select();
    document.execCommand('copy');
    window.getSelection().removeAllRanges();
    
    Filament.Notifications.Notification.make()
        .title('Mensagem copiada!')
        .success()
        .send();
}
</script> 