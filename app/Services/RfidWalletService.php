<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RfidWalletService
{
    /** Lookup active RFID card + wallet by UID. */
    public function findByUid(string $uid): array
    {
        $uid = trim($uid);
        if ($uid === '') {
            throw new RuntimeException('UID kosong.');
        }

        $card = DB::connection('hrd')->table('rfid_cards')
            ->where('uid', $uid)
            ->where('status', 'assigned')
            ->whereNull('deleted_at')
            ->first();

        if (! $card || ! $card->employee_id) {
            throw new RuntimeException('Kartu RFID tidak terdaftar / tidak aktif.');
        }

        $wallet = DB::connection('hrd')->table('wallets')
            ->where('employee_id', $card->employee_id)
            ->where('status', 'active')
            ->first();

        if (! $wallet) {
            throw new RuntimeException('Wallet karyawan tidak ditemukan / frozen.');
        }

        $employee = DB::connection('hrd')->table('employees')
            ->where('id', $card->employee_id)
            ->first();

        return [
            'uid' => $uid,
            'employee_id' => $card->employee_id,
            'employee_name' => $employee->full_name ?? $employee->employee_id ?? 'Karyawan',
            'wallet_id' => $wallet->id,
            'balance' => (float) $wallet->balance,
        ];
    }

    /** Deduct total from wallet. Returns balance after. */
    public function deduct(string $employeeId, float $amount, ?string $notes = null): float
    {
        if ($amount <= 0) {
            throw new RuntimeException('Jumlah potong harus > 0.');
        }

        return DB::connection('hrd')->transaction(function () use ($employeeId, $amount, $notes) {
            $wallet = DB::connection('hrd')->table('wallets')
                ->where('employee_id', $employeeId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException('Wallet tidak ditemukan / frozen.');
            }

            $balance = (float) $wallet->balance;
            if ($balance < $amount) {
                throw new RuntimeException(
                    'Saldo tidak cukup. Saldo: Rp '.number_format($balance, 0, ',', '.').
                    ', total: Rp '.number_format($amount, 0, ',', '.')
                );
            }

            $after = $balance - $amount;

            DB::connection('hrd')->table('wallets')
                ->where('id', $wallet->id)
                ->update(['balance' => $after, 'updated_at' => now()]);

            DB::connection('hrd')->table('wallet_transactions')->insert([
                'id' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'employee_id' => $employeeId,
                'amount' => -$amount, // spend
                'balance_after' => $after,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $after;
        });
    }

    /** Credit wallet back (sale failed after deduct). */
    public function refund(string $employeeId, float $amount, ?string $notes = null): float
    {
        if ($amount <= 0) {
            throw new RuntimeException('Jumlah refund harus > 0.');
        }

        return DB::connection('hrd')->transaction(function () use ($employeeId, $amount, $notes) {
            $wallet = DB::connection('hrd')->table('wallets')
                ->where('employee_id', $employeeId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException('Wallet tidak ditemukan untuk refund.');
            }

            $after = (float) $wallet->balance + $amount;

            DB::connection('hrd')->table('wallets')
                ->where('id', $wallet->id)
                ->update(['balance' => $after, 'updated_at' => now()]);

            DB::connection('hrd')->table('wallet_transactions')->insert([
                'id' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'employee_id' => $employeeId,
                'amount' => $amount,
                'balance_after' => $after,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $after;
        });
    }
}
