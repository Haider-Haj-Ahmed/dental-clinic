<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProviderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Provider::class, 'provider');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $providers = Provider::query()
            ->with('user', 'operatory')
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('specialty', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 20))
            ->withQueryString();

        return ProviderResource::collection($providers);
    }

    public function store(StoreProviderRequest $request): JsonResponse
    {
        $provider = Provider::query()->create($request->validated());

        return response()->json(ProviderResource::make($provider->load('user', 'operatory')), 201);
    }

    public function show(Provider $provider): ProviderResource
    {
        return ProviderResource::make($provider->load('user', 'operatory'));
    }

    public function update(UpdateProviderRequest $request, Provider $provider): ProviderResource
    {
        $provider->update($request->validated());

        return ProviderResource::make($provider->refresh()->load('user', 'operatory'));
    }

    public function destroy(Provider $provider): Response
    {
        $provider->delete();

        return response()->noContent();
    }
}
