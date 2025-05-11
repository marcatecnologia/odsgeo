<?php

namespace App\Traits;

use App\Models\Servico;

trait HasCurrentService
{
    protected function getCurrentService(): ?Servico
    {
        $currentServiceId = session('current_service_id');
        if (!$currentServiceId) {
            return null;
        }

        return Servico::with(['projeto.cliente'])->find($currentServiceId);
    }

    protected function getCurrentServiceId(): ?int
    {
        return session('current_service_id');
    }

    protected function scopeForCurrentService($query)
    {
        $currentServiceId = $this->getCurrentServiceId();
        if (!$currentServiceId) {
            return $query;
        }

        return $query->where('servico_id', $currentServiceId);
    }
} 