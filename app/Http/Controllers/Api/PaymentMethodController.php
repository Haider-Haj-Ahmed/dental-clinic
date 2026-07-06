<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PaymentMethod::class, 'payment_method');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection(
            PaymentMethod::query()
                ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
                ->orderBy('name')
                ->paginate($this->perPage($request))
                ->withQueryString()
        );
    }

    public function store(StorePaymentMethodRequest $request): PaymentMethodResource
    {
        return PaymentMethodResource::make(PaymentMethod::query()->create($request->validated()));
    }

    public function show(PaymentMethod $paymentMethod): PaymentMethodResource
    {
        return PaymentMethodResource::make($paymentMethod);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): PaymentMethodResource
    {
        $paymentMethod->update($request->validated());

        return PaymentMethodResource::make($paymentMethod->refresh());
    }

    public function destroy(PaymentMethod $paymentMethod): Response
    {
        abort_if($paymentMethod->payments()->exists(), 422, 'Cannot delete a payment method that has been used.');

        $paymentMethod->delete();

        return $this->noContentResponse();
    }
}
