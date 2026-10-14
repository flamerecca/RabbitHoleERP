<?php

namespace App\Http\Controllers;

use App\Actions\Accounting\CancelPayment;
use App\Actions\Accounting\ConfirmPayment;
use App\Actions\Accounting\CreatePayment;
use App\Actions\Accounting\CreatePaymentAllocations;
use App\Actions\Accounting\UpdatePayment;
use App\Enums\DocumentStatus;
use App\Enums\PartnerType;
use App\Enums\PaymentType;
use App\Http\Requests\StorePaymentAllocationsRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentAllocationResource;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * List the receipts and payments of the team.
     */
    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'payment_type' => ['nullable', Rule::enum(PaymentType::class)],
            'partner_type' => ['nullable', Rule::enum(PartnerType::class)],
            'partner_id' => ['nullable', 'integer'],
        ]);

        $payments = $team->payments()
            ->when($validated['status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->when($validated['payment_type'] ?? null, fn ($query, string $value) => $query->where('payment_type', $value))
            ->when($validated['partner_type'] ?? null, fn ($query, string $value) => $query->where('partner_type', $value))
            ->when($validated['partner_id'] ?? null, fn ($query, int|string $value) => $query->where('partner_id', $value))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return PaymentResource::collection($payments);
    }

    /**
     * Create a draft receipt or payment.
     */
    public function store(StorePaymentRequest $request, Team $team, CreatePayment $create): JsonResponse
    {
        return new PaymentResource($create->handle($team, $request->user(), $request->validated()))->response()->setStatusCode(201);
    }

    /**
     * Create, confirm and allocate a receipt from a customer in one request, for payments taken at the counter.
     *
     * The validation and the number are written here on purpose: receipts and payments share one counter instead of
     * the sequences of DocumentSequence, see docs/architecture/document-customization.md section 6.1.
     */
    public function quickReceipt(Request $request, Team $team, ConfirmPayment $confirm, CreatePaymentAllocations $allocate): JsonResponse
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('team_id', $team->id)],
            'journal_id' => ['required', 'integer', Rule::exists('journals', 'id')->where('team_id', $team->id)->whereIn('type', ['cash', 'bank'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('team_id', $team->id)],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'allocations' => ['sometimes', 'array'],
            'allocations.*.journal_entry_line_id' => ['required', 'integer', 'distinct'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $payment = DB::transaction(function () use ($request, $team, $validated, $confirm, $allocate) {
            $month = substr($validated['payment_date'], 0, 4).substr($validated['payment_date'], 5, 2);
            $lastNumber = DB::table('payments')
                ->where('team_id', $team->id)
                ->where(fn ($query) => $query->where('payment_no', 'like', 'RC'.$month.'/%')->orWhere('payment_no', 'like', 'PY'.$month.'/%'))
                ->pluck('payment_no')
                ->map(fn (string $paymentNo) => (int) substr($paymentNo, strlen('RC'.$month.'/')))
                ->max();
            $sequence = ($lastNumber ?? 0) + 1;
            while (DB::table('payments')->where('team_id', $team->id)->where('payment_no', 'RC'.$month.'/'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT))->exists()) {
                $sequence++;
            }

            $payment = Payment::create([
                'team_id' => $team->id,
                'payment_no' => 'RC'.$month.'/'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                'payment_type' => 'receipt',
                'partner_type' => 'customer',
                'partner_id' => $validated['partner_id'],
                'journal_id' => $validated['journal_id'],
                'amount' => $validated['amount'],
                'currency_id' => $validated['currency_id'],
                'exchange_rate' => $validated['exchange_rate'],
                'payment_date' => $validated['payment_date'],
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            $payment = $confirm->handle($payment, $request->user());

            if (! empty($validated['allocations'])) {
                $allocate->handle($payment, $validated['allocations']);
            }

            return $payment;
        });

        return new PaymentResource($payment->fresh())->response()->setStatusCode(201);
    }

    /**
     * Show the payment.
     */
    public function show(Team $team, Payment $payment): PaymentResource
    {
        return new PaymentResource($payment);
    }

    /**
     * Update the draft payment.
     */
    public function update(UpdatePaymentRequest $request, Team $team, Payment $payment, UpdatePayment $update): PaymentResource
    {
        return new PaymentResource($update->handle($payment, $request->validated()));
    }

    /**
     * Confirm the draft payment and post it.
     */
    public function confirm(Request $request, Team $team, Payment $payment, ConfirmPayment $confirm): PaymentResource
    {
        return new PaymentResource($confirm->handle($payment, $request->user()));
    }

    /**
     * Cancel the payment, giving back what it settled.
     */
    public function cancel(Team $team, Payment $payment, CancelPayment $cancel): PaymentResource
    {
        return new PaymentResource($cancel->handle($payment));
    }

    /**
     * List the allocations of the payment.
     */
    public function allocations(Request $request, Team $team, Payment $payment): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return PaymentAllocationResource::collection($payment->allocations()->orderBy('id')->paginate($validated['per_page'] ?? 15)->withQueryString());
    }

    /**
     * Settle receivable or payable lines with the confirmed payment.
     */
    public function allocate(StorePaymentAllocationsRequest $request, Team $team, Payment $payment, CreatePaymentAllocations $allocate): JsonResponse
    {
        /** @var list<array{journal_entry_line_id: int, amount: float|int|string}> $allocations */
        $allocations = $request->validated('allocations');

        return PaymentAllocationResource::collection($allocate->handle($payment, $allocations))->response()->setStatusCode(201);
    }
}
