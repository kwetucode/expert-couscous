<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransferResource;
use App\Services\StoreTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferApiController extends Controller
{
    public function __construct(
        private StoreTransferService $transferService
    ) {}

    /**
     * Get all transfers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $status = $request->query('status');
            $storeId = $request->query('store_id');
            $fromStoreId = $request->query('from_store_id');
            $toStoreId = $request->query('to_store_id');
            $direction = $request->query('direction'); // 'outgoing', 'incoming', 'all'
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $perPage = (int) $request->query('per_page', 15);

            // Build query
            $query = $this->transferService->getAllTransfers(
                search: $search,
                status: $status,
                sortBy: $sortBy,
                sortDirection: $sortDirection
            );

            // Apply store filters
            if ($storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->where('from_store_id', $storeId)
                        ->orWhere('to_store_id', $storeId);
                });
            }

            if ($fromStoreId) {
                $query->where('from_store_id', $fromStoreId);
            }

            if ($toStoreId) {
                $query->where('to_store_id', $toStoreId);
            }

            // Apply direction filter for authenticated user's store
            if ($direction && auth()->user()->current_store_id) {
                $currentStoreId = auth()->user()->current_store_id;

                if ($direction === 'outgoing') {
                    $query->where('from_store_id', $currentStoreId);
                } elseif ($direction === 'incoming') {
                    $query->where('to_store_id', $currentStoreId);
                } else {
                    $query->where(function ($q) use ($currentStoreId) {
                        $q->where('from_store_id', $currentStoreId)
                            ->orWhere('to_store_id', $currentStoreId);
                    });
                }
            }

            // Paginate
            $transfers = $query->with(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver', 'canceller'])
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => TransferResource::collection($transfers),
                'meta' => [
                    'current_page' => $transfers->currentPage(),
                    'last_page' => $transfers->lastPage(),
                    'per_page' => $transfers->perPage(),
                    'total' => $transfers->total(),
                    'from' => $transfers->firstItem(),
                    'to' => $transfers->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific transfer
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->findTransfer($id);

            if (!$transfer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfer not found',
                ], 404);
            }

            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver', 'canceller']);

            return response()->json([
                'success' => true,
                'data' => new TransferResource($transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new transfer
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'expected_arrival_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['requested_by'] = auth()->id();

            $transfer = $this->transferService->createTransfer($data);
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'canceller']);

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully',
                'data' => new TransferResource($transfer),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a transfer
     *
     * @param int $id
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->approveTransfer($id, auth()->id());
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'canceller']);

            return response()->json([
                'success' => true,
                'message' => 'Transfer approved successfully',
                'data' => new TransferResource($transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive a transfer
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantities' => 'required|array',
            'quantities.*' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfer = $this->transferService->receiveTransfer(
                $id,
                $request->quantities,
                auth()->id(),
                $request->notes
            );
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver', 'canceller']);

            return response()->json([
                'success' => true,
                'message' => 'Transfer received successfully',
                'data' => new TransferResource($transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a transfer
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfer = $this->transferService->cancelTransfer($id, auth()->id(), $request->reason);
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'canceller']);

            return response()->json([
                'success' => true,
                'message' => 'Transfer cancelled successfully',
                'data' => new TransferResource($transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
