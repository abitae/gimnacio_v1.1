<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Support\ClientePortalQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ClienteAppAuthService
{
    public function activar(string $tipoDocumento, string $numeroDocumento, string $password): array
    {
        $cliente = ClientePortalQuery::findClienteByDocumento($tipoDocumento, $numeroDocumento);

        if ($cliente === null) {
            throw ValidationException::withMessages([
                'numero_documento' => 'No encontramos tu documento. Acércate a recepción.',
            ]);
        }

        if (ClientePortalQuery::accounts()->where('cliente_id', $cliente->id)->exists()) {
            throw ValidationException::withMessages([
                'numero_documento' => 'Ya tienes cuenta. Inicia sesión.',
            ]);
        }

        $account = ClienteAppAccount::query()->create([
            'cliente_id' => $cliente->id,
            'password' => $password,
        ]);

        return $this->issueToken($account, $cliente);
    }

    public function login(string $tipoDocumento, string $numeroDocumento, string $password): array
    {
        $cliente = ClientePortalQuery::findClienteByDocumento($tipoDocumento, $numeroDocumento);
        $account = $cliente
            ? ClientePortalQuery::accounts()->where('cliente_id', $cliente->id)->first()
            : null;

        if ($cliente === null || $account === null) {
            if ($cliente !== null && $account === null) {
                throw ValidationException::withMessages([
                    'numero_documento' => 'Primero crea tu contraseña.',
                ]);
            }

            throw ValidationException::withMessages([
                'numero_documento' => 'Documento o contraseña incorrectos.',
            ]);
        }

        if (! Hash::check($password, $account->password)) {
            throw ValidationException::withMessages([
                'password' => 'Documento o contraseña incorrectos.',
            ]);
        }

        return $this->issueToken($account, $cliente);
    }

    public function logout(ClienteAppAccount $account): void
    {
        $account->currentAccessToken()?->delete();
    }

    public function cambiarPassword(ClienteAppAccount $account, string $passwordActual, string $passwordNueva): void
    {
        if (! Hash::check($passwordActual, $account->password)) {
            throw ValidationException::withMessages([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        $account->forceFill([
            'password' => $passwordNueva,
        ])->save();

        $account->tokens()->delete();
    }

    public function establecerPassword(Cliente $cliente, string $password): ClienteAppAccount
    {
        $account = ClientePortalQuery::accounts()->where('cliente_id', $cliente->id)->first();

        if ($account === null) {
            return ClienteAppAccount::query()->create([
                'cliente_id' => $cliente->id,
                'password' => $password,
            ]);
        }

        $account->forceFill([
            'password' => $password,
        ])->save();

        $account->tokens()->delete();

        return $account->fresh();
    }

    public function resetearAcceso(Cliente $cliente): bool
    {
        $account = ClientePortalQuery::accounts()->where('cliente_id', $cliente->id)->first();

        if ($account === null) {
            return false;
        }

        $account->tokens()->delete();
        $account->delete();

        return true;
    }

    /**
     * @return array{token: string, account: ClienteAppAccount, cliente: Cliente}
     */
    protected function issueToken(ClienteAppAccount $account, Cliente $cliente): array
    {
        $account->forceFill(['last_login_at' => now()])->save();

        $token = $account->createToken('mobile')->plainTextToken;

        return [
            'token' => $token,
            'account' => $account->fresh(),
            'cliente' => $cliente,
        ];
    }
}
