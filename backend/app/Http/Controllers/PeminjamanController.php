<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Models\Peminjaman;
use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $query = Peminjaman::with([
            'user:id,name,email',
            'lapangan:id,nama_lapangan,tipe,harga_per_jam'
        ]);

        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lapangan_id' => 'required|exists:lapangans,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ], [
            'lapangan_id.required' => 'Lapangan wajib dipilih.',
            'lapangan_id.exists' => 'Lapangan tidak ditemukan.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $lapangan = Lapangan::findOrFail($data['lapangan_id']);

        if ($lapangan->status !== 'tersedia') {
            return response()->json([
                'success' => false,
                'message' => 'Lapangan sedang tidak tersedia.'
            ], 422);
        }

        $bentrok = Peminjaman::where(
            'lapangan_id',
            $data['lapangan_id']
        )
            ->where('tanggal', $data['tanggal'])
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->where(function ($query) use ($data) {
                $query
                    ->where('jam_mulai', '<', $data['jam_selesai'])
                    ->where('jam_selesai', '>', $data['jam_mulai']);
            })
            ->exists();

        if ($bentrok) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tersebut sudah digunakan.'
            ], 422);
        }

        $peminjaman = Peminjaman::create([
            'user_id' => $request->user()->id,
            'lapangan_id' => $data['lapangan_id'],
            'tanggal' => $data['tanggal'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
            'status' => 'menunggu',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman berhasil ditambahkan.',
            'data' => $peminjaman->load(['user', 'lapangan'])
        ], 201);
    }

    public function show(
        Request $request,
        Peminjaman $peminjaman
    ) {
        if (
            $request->user()->role !== 'admin' &&
            $peminjaman->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $peminjaman->load(['user', 'lapangan'])
        ]);
    }

    public function update(
        Request $request,
        Peminjaman $peminjaman
    ) {
        if (
            $request->user()->role !== 'admin' &&
            $peminjaman->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses.'
            ], 403);
        }

        if (
            $request->user()->role !== 'admin' &&
            $peminjaman->status !== 'menunggu'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Peminjaman yang sudah diproses tidak dapat diedit.'
            ], 422);
        }

        $data = $request->validate([
            'lapangan_id' => 'required|exists:lapangans,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ], [
            'lapangan_id.required' => 'Lapangan wajib dipilih.',
            'lapangan_id.exists' => 'Lapangan tidak ditemukan.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $lapangan = Lapangan::findOrFail($data['lapangan_id']);

        if ($lapangan->status !== 'tersedia') {
            return response()->json([
                'success' => false,
                'message' => 'Lapangan sedang tidak tersedia.'
            ], 422);
        }

        $bentrok = Peminjaman::where(
            'lapangan_id',
            $data['lapangan_id']
        )
            ->where('tanggal', $data['tanggal'])
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->where('id', '!=', $peminjaman->id)
            ->where(function ($query) use ($data) {
                $query
                    ->where('jam_mulai', '<', $data['jam_selesai'])
                    ->where('jam_selesai', '>', $data['jam_mulai']);
            })
            ->exists();

        if ($bentrok) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tersebut sudah digunakan.'
            ], 422);
        }

        $peminjaman->update([
            'lapangan_id' => $data['lapangan_id'],
            'tanggal' => $data['tanggal'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman berhasil diperbarui.',
            'data' => $peminjaman->fresh()->load([
                'user',
                'lapangan'
            ])
        ]);
    }

    public function updateStatus(
        Request $request,
        Peminjaman $peminjaman
    ) {
        $data = $request->validate([
            'status' => 'required|in:menunggu,disetujui,ditolak',
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        if ($data['status'] === 'disetujui') {
            $bentrok = Peminjaman::where(
                'lapangan_id',
                $peminjaman->lapangan_id
            )
                ->where('tanggal', $peminjaman->tanggal)
                ->where('id', '!=', $peminjaman->id)
                ->where('status', 'disetujui')
                ->where(function ($query) use ($peminjaman) {
                    $query
                        ->where(
                            'jam_mulai',
                            '<',
                            $peminjaman->jam_selesai
                        )
                        ->where(
                            'jam_selesai',
                            '>',
                            $peminjaman->jam_mulai
                        );
                })
                ->exists();

            if ($bentrok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat disetujui karena jadwal bentrok.'
                ], 422);
            }
        }

        $peminjaman->update([
            'status' => $data['status']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
            'data' => $peminjaman->fresh()->load([
                'user',
                'lapangan'
            ])
        ]);
    }
}