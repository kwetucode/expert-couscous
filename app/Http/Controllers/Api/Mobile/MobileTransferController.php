<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransferResource;
use App\Services\StoreTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Controller API Mobile - Transferts Inter-Magasins
 *
 * Fournit les endpoints pour la gestion des transferts sur mobile
 */
class MobileTransferController extends Controller
{
    public function __construct(
        private StoreTransferService $transferService
    ) {}

    /**
     * Liste des transferts pour le mobile
     *
     * GET /api/mobile/transfers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $status = $request->query('status');
            $direction = $request->query('direction', 'all'); // 'outgoing', 'incoming', 'all'
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $perPage = min((int) $request->query('per_page', 15), 50); // Max 50 pour mobile

            $user = Auth::user();
            $currentStoreId = $user->current_store_id;

            if (!$currentStoreId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun magasin actif sélectionné',
                ], 400);
            }

            // Build query
            $query = $this->transferService->getAllTransfers(
                search: $search,
                status: $status,
                sortBy: $sortBy,
                sortDirection: $sortDirection
            );

            // Apply direction filter based on current store
            if ($direction === 'outgoing') {
                $query->where('from_store_id', $currentStoreId);
            } elseif ($direction === 'incoming') {
                $query->where('to_store_id', $currentStoreId);
            } else {
                // All transfers related to current store
                $query->where(function ($q) use ($currentStoreId) {
                    $q->where('from_store_id', $currentStoreId)
                        ->orWhere('to_store_id', $currentStoreId);
                });
            }

            // Paginate
            $transfers = $query->with(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver'])
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
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transferts',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Détails d'un transfert
     *
     * GET /api/mobile/transfers/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->findTransfer($id);

            if (!$transfer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfert non trouvé',
                ], 404);
            }

            // Vérifier que l'utilisateur a accès à ce transfert
            $user = Auth::user();
            $currentStoreId = $user->current_store_id;

            if ($transfer->from_store_id !== $currentStoreId && $transfer->to_store_id !== $currentStoreId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à ce transfert',
                ], 403);
            }

            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver', 'canceller']);

            return response()->json([
                'success' => true,
                'data' => new TransferResource($transfer),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du transfert',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Créer un nouveau transfert
     *
     * POST /api/mobile/transfers
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ], [
            'from_store_id.required' => 'Le magasin source est requis',
            'to_store_id.required' => 'Le magasin destination est requis',
            'to_store_id.different' => 'Les magasins source et destination doivent être différents',
            'items.required' => 'Au moins un produit est requis',
            'items.min' => 'Au moins un produit est requis',
            'items.*.product_variant_id.required' => 'Le produit est requis',
            'items.*.quantity.required' => 'La quantité est requise',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['requested_by'] = Auth::id();

            $transfer = $this->transferService->createTransfer($data);
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester']);

            return response()->json([
                'success' => true,
                'message' => 'Transfert créé avec succès',
                'data' => new TransferResource($transfer),
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du transfert',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approuver un transfert
     *
     * POST /api/mobile/transfers/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $transfer = $this->transferService->findTransfer($id);

            if (!$transfer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfert non trouvé',
                ], 404);
            }

            // Vérifier les permissions
            if (!$transfer->canBeApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce transfert ne peut pas être approuvé dans son état actuel',
                ], 400);
            }

            $transfer = $this->transferService->approveTransfer($id, Auth::id());
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver']);

            return response()->json([
                'success' => true,
                'message' => 'Transfert approuvé avec succès',
                'data' => new TransferResource($transfer),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Réceptionner un transfert
     *
     * POST /api/mobile/transfers/{id}/receive
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantities' => 'required|array',
            'quantities.*' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'quantities.required' => 'Les quantités sont requises',
            'quantities.*.required' => 'Toutes les quantités doivent être renseignées',
            'quantities.*.min' => 'Les quantités doivent être positives ou nulles',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfer = $this->transferService->findTransfer($id);

            if (!$transfer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfert non trouvé',
                ], 404);
            }

            if (!$transfer->canBeReceived()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce transfert ne peut pas être réceptionné dans son état actuel',
                ], 400);
            }

            $transfer = $this->transferService->receiveTransfer(
                $id,
                $request->quantities,
                Auth::id(),
                $request->notes
            );
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'receiver']);

            return response()->json([
                'success' => true,
                'message' => 'Transfert réceptionné avec succès',
                'data' => new TransferResource($transfer),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Annuler un transfert
     *
     * POST /api/mobile/transfers/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'La raison de l\'annulation est requise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfer = $this->transferService->findTransfer($id);

            if (!$transfer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfert non trouvé',
                ], 404);
            }

            if (!$transfer->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce transfert ne peut pas être annulé dans son état actuel',
                ], 400);
            }

            $transfer = $this->transferService->cancelTransfer($id, Auth::id(), $request->reason);
            $transfer->load(['fromStore', 'toStore', 'items.variant.product', 'requester', 'approver', 'canceller']);

            return response()->json([
                'success' => true,
                'message' => 'Transfert annulé avec succès',
                'data' => new TransferResource($transfer),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Statistiques des transferts pour le magasin actif
     *
     * GET /api/mobile/transfers/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            $currentStoreId = $user->current_store_id;

            if (!$currentStoreId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun magasin actif sélectionné',
                ], 400);
            }

            $stats = $this->transferService->getTransferStatistics($currentStoreId);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
