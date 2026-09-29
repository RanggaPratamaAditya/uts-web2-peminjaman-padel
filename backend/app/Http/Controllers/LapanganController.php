<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;

class LapanganController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Lapangan::where('status', 'tersedia')
                ->orderBy('nama_lapangan')
                ->get()
        ]);
    }
}