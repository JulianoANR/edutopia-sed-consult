<?php

namespace App\Http\Controllers;

use App\Jobs\ExportSchoolStudentsJob;
use App\Models\StudentExportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ReflectionClass;

class StudentExportRequestController extends Controller
{
    /**
     * Página com histórico e status da exportação.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $requests = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (StudentExportRequest $r) {
                return [
                    'id' => $r->id,
                    'status' => $r->status,
                    'ano_letivo' => $r->ano_letivo,
                    'school_codes' => $r->school_codes,
                    'selected_fields' => $r->selected_fields ?? [],
                    'progress_current' => $r->progress_current,
                    'file_path' => $r->file_path,
                    'error_message' => $r->error_message,
                    'created_at' => optional($r->created_at)->toDateTimeString(),
                    'updated_at' => optional($r->updated_at)->toDateTimeString(),
                ];
            });

        $latestDone = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'done')
            ->orderByDesc('id')
            ->first();

        $hasLatestFile = $latestDone?->file_path ? Storage::exists($latestDone->file_path) : false;

        $active = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderByDesc('id')
            ->first();

        return Inertia::render('StudentExports/Index', [
            'requests' => $requests,
            'active' => $active ? [
                'id' => $active->id,
                'status' => $active->status,
                'ano_letivo' => $active->ano_letivo,
                'school_codes' => $active->school_codes,
                'progress_current' => $active->progress_current,
                'error_message' => $active->error_message,
                'created_at' => optional($active->created_at)->toDateTimeString(),
                'updated_at' => optional($active->updated_at)->toDateTimeString(),
            ] : null,
            'latest_done' => $latestDone ? [
                'id' => $latestDone->id,
                'file_path' => $latestDone->file_path,
                'created_at' => optional($latestDone->created_at)->toDateTimeString(),
            ] : null,
            'has_latest_file' => $hasLatestFile,
        ]);
    }

    /**
     * Inicia uma exportação assíncrona (uma por vez por tenant).
     */
    public function start(Request $request)
    {
        $request->validate([
            'ano_letivo' => 'required|string|size:4',
            'schools' => 'required|array|min:1',
            'schools.*.code' => 'required|string',
            'schools.*.name' => 'required|string',
            'selected_fields' => 'array',
            'force' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        $force = (bool) $request->input('force', false);

        $exportRequest = DB::transaction(function () use ($request, $user, $tenantId, $force) {
            $active = StudentExportRequest::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'processing'])
                ->lockForUpdate()
                ->first();

            if ($active) {
                if (!$force) {
                    return null;
                }

                // Cancelar exportação ativa (e qualquer outra pendente/processando) para permitir reinício.
                StudentExportRequest::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['pending', 'processing'])
                    ->update([
                        'status' => 'cancelled',
                        'error_message' => 'Cancelada pelo usuário para iniciar uma nova exportação.',
                    ]);
            }

            // Limpar último arquivo antes de iniciar um novo processamento.
            $latestPath = "exports/{$tenantId}/alunos_export_latest.csv";
            if (Storage::exists($latestPath)) {
                Storage::delete($latestPath);
            }

            return StudentExportRequest::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'school_codes' => $request->input('schools'),
                'ano_letivo' => $request->input('ano_letivo'),
                'selected_fields' => $request->input('selected_fields', []),
                'status' => 'pending',
                'progress_current' => 0,
            ]);
        });

        if (!$exportRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe uma exportação em andamento. Se você continuar, a exportação atual será cancelada e uma nova será iniciada.',
                'can_force' => true,
            ], 409);
        }

        ExportSchoolStudentsJob::dispatch($exportRequest->id, $user->id)->onQueue('exports');

        return response()->json([
            'success' => true,
            'message' => 'Exportação iniciada. O processamento pode levar alguns minutos.',
            'export_request_id' => $exportRequest->id,
        ]);
    }

    /**
     * Cancela uma exportação pendente ou em processamento (cooperativo no job),
     * remove o job da fila se ainda não tiver sido executado e limpa dados auxiliares.
     */
    public function cancel(Request $request, StudentExportRequest $studentExportRequest): JsonResponse
    {
        $user = $request->user();

        if ((int) $studentExportRequest->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }

        if (!in_array($studentExportRequest->status, ['pending', 'processing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Somente exportações pendentes ou em processamento podem ser canceladas.',
            ], 422);
        }

        $tenantId = (int) $user->tenant_id;
        $removedJobs = 0;

        DB::transaction(function () use ($studentExportRequest, $tenantId, &$removedJobs) {
            $removedJobs = $this->removeQueuedExportJobsForRequest($studentExportRequest->id);

            if ($studentExportRequest->file_path && Storage::exists($studentExportRequest->file_path)) {
                Storage::delete($studentExportRequest->file_path);
            }

            $studentExportRequest->update([
                'status' => 'cancelled',
                'error_message' => 'Cancelado pelo usuário.',
                'file_path' => null,
                'progress_current' => 0,
            ]);
        });

        Log::info('StudentExportRequest: cancelado pelo usuário', [
            'export_request_id' => $studentExportRequest->id,
            'tenant_id' => $tenantId,
            'jobs_removed' => $removedJobs,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exportação cancelada.',
            'jobs_removed_from_queue' => $removedJobs,
        ]);
    }

    /**
     * Remove da tabela jobs entradas da fila "exports" ligadas a este export_request_id.
     */
    private function removeQueuedExportJobsForRequest(int $exportRequestId): int
    {
        $removed = 0;
        $jobs = DB::table('jobs')->where('queue', 'exports')->get();

        foreach ($jobs as $job) {
            try {
                $payload = json_decode($job->payload, true);
                if (!is_array($payload) || !isset($payload['data']['command'])) {
                    continue;
                }
                $command = unserialize($payload['data']['command'], [
                    'allowed_classes' => [ExportSchoolStudentsJob::class],
                ]);
                if (!$command instanceof ExportSchoolStudentsJob) {
                    continue;
                }
                $ref = new ReflectionClass($command);
                $prop = $ref->getProperty('exportRequestId');
                $prop->setAccessible(true);
                if ((int) $prop->getValue($command) !== $exportRequestId) {
                    continue;
                }
                DB::table('jobs')->where('id', $job->id)->delete();
                $removed++;
            } catch (\Throwable $e) {
                // ignora payloads que não deserializam
            }
        }

        return $removed;
    }

    /**
     * Retorna status em JSON para polling.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $active = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderByDesc('id')
            ->first();

        $latestDone = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'done')
            ->orderByDesc('id')
            ->first();

        $hasLatestFile = $latestDone?->file_path ? Storage::exists($latestDone->file_path) : false;

        return response()->json([
            'success' => true,
            'active' => $active ? [
                'id' => $active->id,
                'status' => $active->status,
                'ano_letivo' => $active->ano_letivo,
                'school_codes' => $active->school_codes,
                'progress_current' => $active->progress_current,
                'error_message' => $active->error_message,
                'created_at' => optional($active->created_at)->toDateTimeString(),
                'updated_at' => optional($active->updated_at)->toDateTimeString(),
            ] : null,
            'latest_done' => $latestDone ? [
                'id' => $latestDone->id,
                'file_path' => $latestDone->file_path,
                'created_at' => optional($latestDone->created_at)->toDateTimeString(),
            ] : null,
            'has_latest_file' => $hasLatestFile,
        ]);
    }

    /**
     * Download do último arquivo gerado (sempre o mesmo path por tenant).
     */
    public function downloadLatest(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $latestDone = StudentExportRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'done')
            ->orderByDesc('id')
            ->first();

        if (!$latestDone || !$latestDone->file_path || !Storage::exists($latestDone->file_path)) {
            abort(404);
        }

        $filename = 'alunos_export_latest.csv';
        return Storage::download($latestDone->file_path, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

