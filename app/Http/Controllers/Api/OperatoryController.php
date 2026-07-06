<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperatoryRequest;
use App\Http\Requests\UpdateOperatoryRequest;
use App\Http\Resources\OperatoryResource;
use App\Models\Operatory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OperatoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Operatory::class, 'operatory');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $operatories = Operatory::query()
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return OperatoryResource::collection($operatories);
    }

    public function store(StoreOperatoryRequest $request): OperatoryResource
    {
        $operatory = Operatory::query()->create($request->validated());

        return OperatoryResource::make($operatory);
    }

    public function show(Operatory $operatory): OperatoryResource
    {
        return OperatoryResource::make($operatory);
    }

    public function update(UpdateOperatoryRequest $request, Operatory $operatory): OperatoryResource
    {
        $operatory->update($request->validated());

        return OperatoryResource::make($operatory->refresh());
    }

    public function destroy(Operatory $operatory): Response
    {
        $operatory->delete();

        return $this->noContentResponse();
    }
}
