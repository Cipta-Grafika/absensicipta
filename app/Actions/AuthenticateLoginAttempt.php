<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateLoginAttempt
{
    public function __invoke(Request $request)
    {
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::where('phone', $request->email)->first();
        }

        if ($user && Hash::check($request->password, $user->password)) {
            if (in_array($user->status, ['inactive', 'resign', 'fired'])) {
                $statusText = match ($user->status) {
                    'inactive' => 'Tidak Aktif',
                    'resign' => 'Mengundurkan Diri',
                    'fired' => 'Dipecat',
                    default => 'Non-Aktif',
                };
                throw ValidationException::withMessages([
                    'email' => __('Akun Anda sudah tidak aktif (Status: ' . $statusText . '). Silakan hubungi administrator.'),
                ]);
            }
            return $user;
        }
    }
}
