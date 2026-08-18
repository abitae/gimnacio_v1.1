<?php

namespace Database\Factories;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClienteAppAccount>
 */
class ClienteAppAccountFactory extends Factory
{
    protected $model = ClienteAppAccount::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'password' => 'password123',
            'last_login_at' => null,
        ];
    }
}
