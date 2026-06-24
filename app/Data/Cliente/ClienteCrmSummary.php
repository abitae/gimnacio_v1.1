<?php

namespace App\Data\Cliente;

final readonly class ClienteCrmSummary
{
    /**
     * @param  array<string, mixed>|null  $lastActivity
     * @param  array<string, mixed>|null  $linkedLead
     */
    public function __construct(
        public int $tagsCount,
        public int $openTasksCount,
        public ?array $lastActivity,
        public ?array $linkedLead,
    ) {}
}
