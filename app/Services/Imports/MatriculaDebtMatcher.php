<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use Carbon\CarbonImmutable;

class MatriculaDebtMatcher
{
    /**
     * @return array{status:string,matricula:?ClienteMatricula,warnings:array<int,string>}
     */
    public function findFor(Cliente $cliente, DeudaClienteRowData $row): array
    {
        $exactMatches = $this->candidateQuery($cliente)
            ->get()
            ->filter(fn (ClienteMatricula $matricula) => $this->planMatches($matricula, $row->plan))
            ->filter(fn (ClienteMatricula $matricula) => $this->sameDate($matricula->fecha_inicio, $row->fechaInicio) && $this->sameDate($matricula->fecha_fin, $row->fechaFin))
            ->values();

        if ($exactMatches->count() === 1) {
            return [
                'status' => 'matched_exact',
                'matricula' => $exactMatches->first(),
                'warnings' => [],
            ];
        }

        if ($exactMatches->count() > 1) {
            return [
                'status' => 'ambiguous',
                'matricula' => null,
                'warnings' => ['Se encontraron multiples matriculas exactas para la misma fila.'],
            ];
        }

        $flexibleMatches = $this->candidateQuery($cliente)
            ->get()
            ->filter(fn (ClienteMatricula $matricula) => $this->planMatches($matricula, $row->plan))
            ->map(function (ClienteMatricula $matricula) use ($row): array {
                return [
                    'matricula' => $matricula,
                    'score' => $this->distanceScore($matricula, $row),
                ];
            })
            ->filter(fn (array $candidate) => $candidate['score'] !== null)
            ->sortBy('score')
            ->values();

        if ($flexibleMatches->isEmpty()) {
            return [
                'status' => 'not_found',
                'matricula' => null,
                'warnings' => ['No se encontro matricula compatible.'],
            ];
        }

        $best = $flexibleMatches->first();
        $bestScore = $best['score'];
        $ties = $flexibleMatches->filter(fn (array $candidate) => $candidate['score'] === $bestScore);

        if ($ties->count() > 1) {
            return [
                'status' => 'ambiguous',
                'matricula' => null,
                'warnings' => ['Hay mas de una matricula candidata con la misma distancia de fechas.'],
            ];
        }

        return [
            'status' => 'matched_flexible',
            'matricula' => $best['matricula'],
            'warnings' => [],
        ];
    }

    private function candidateQuery(Cliente $cliente)
    {
        return ClienteMatricula::query()
            ->with('membresia')
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'membresia')
            ->where('estado', '!=', 'cancelada');
    }

    private function planMatches(ClienteMatricula $matricula, ?string $plan): bool
    {
        return $this->normalizePlan($matricula->membresia?->nombre) === $this->normalizePlan($plan);
    }

    private function normalizePlan(?string $value): string
    {
        $normalized = DeudaClienteRowData::normalizeComparable($value);

        return str_replace([' + ', '+'], ' ', $normalized);
    }

    private function sameDate($left, ?CarbonImmutable $right): bool
    {
        if ($left === null || $right === null) {
            return $left === null && $right === null;
        }

        return $left->toDateString() === $right->toDateString();
    }

    private function distanceScore(ClienteMatricula $matricula, DeudaClienteRowData $row): ?int
    {
        if ($row->fechaInicio === null || $row->fechaFin === null || $matricula->fecha_inicio === null || $matricula->fecha_fin === null) {
            return null;
        }

        $inicioDiff = abs($matricula->fecha_inicio->diffInDays($row->fechaInicio));
        $finDiff = abs($matricula->fecha_fin->diffInDays($row->fechaFin));

        if ($inicioDiff > 7 || $finDiff > 7) {
            return null;
        }

        return $inicioDiff + $finDiff;
    }
}
