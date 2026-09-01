<?php

namespace App\Data\Cliente;

final readonly class ClienteCrmSummary
{
    /**
     * @param  array<string, mixed>|null  $lastActivity
     * @param  array<string, mixed>|null  $linkedLead
     * @param  list<array<string, mixed>>  $recentActivities
     * @param  list<array<string, mixed>>  $pendingTasks
     * @param  list<array<string, mixed>>  $openDeals
     */
    public function __construct(
        public int $tagsCount,
        public int $openTasksCount,
        public ?array $lastActivity,
        public ?array $linkedLead,
        public ?int $asesorCrmId = null,
        public ?string $asesorCrmNombre = null,
        public int $openDealsCount = 0,
        public array $recentActivities = [],
        public array $pendingTasks = [],
        public array $openDeals = [],
    ) {}
}
