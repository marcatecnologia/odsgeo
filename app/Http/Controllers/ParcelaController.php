<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use Illuminate\Support\Facades\Auth;

class ParcelaController extends Controller
{
    public function salvar(Request $request)
    {
        $request->validate([
            'cod_ccir' => 'required|string'
        ]);

        try {
            $parcela = Parcela::create([
                'cod_ccir' => $request->cod_ccir,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Parcela salva com sucesso',
                'data' => $parcela
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar parcela: ' . $e->getMessage()
            ], 500);
        }
    }
} 