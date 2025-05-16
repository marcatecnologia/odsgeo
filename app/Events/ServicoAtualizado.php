<?php

namespace App\Events;

use App\Models\Servico;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServicoAtualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $servico;
    
    public function __construct(Servico $servico)
    {
        $this->servico = $servico;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('servico.' . $this->servico->id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->servico->id,
            'nome' => $this->servico->nome,
            'projeto_id' => $this->servico->projeto_id,
            'cliente_id' => $this->servico->projeto->cliente_id,
            'updated_at' => $this->servico->updated_at
        ];
    }
} 