<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedureCodeRequest;
use App\Http\Requests\UpdateProcedureCodeRequest;
use App\Http\Resources\ProcedureCodeResource;
use App\Models\ProcedureCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProcedureCodeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProcedureCode::class, 'procedure_code');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $codes = ProcedureCode::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->string('search'));
                $q->where(fn ($sub) => $sub
                    ->where('code', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%"));
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ProcedureCodeResource::collection($codes);
    }

    public function store(StoreProcedureCodeRequest $request): ProcedureCodeResource
    {
        $code = ProcedureCode::query()->create($request->validated());

        return ProcedureCodeResource::make($code);
    }

    public function show(ProcedureCode $procedureCode): ProcedureCodeResource
    {
        return ProcedureCodeResource::make($procedureCode);
    }

    public function update(UpdateProcedureCodeRequest $request, ProcedureCode $procedureCode): ProcedureCodeResource
    {
        $procedureCode->update($request->validated());

        return ProcedureCodeResource::make($procedureCode->refresh());
    }

    public function destroy(ProcedureCode $procedureCode): Response
    {
        $procedureCode->delete();

        return $this->noContentResponse();
    }
}
