<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StoreService;
use App\Services\StoreTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreApiController extends Controller
{
    public function __construct(
        private StoreService $storeService
    ) {}

    /**
     * Get all stores
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'name');
        $sortDirection = $request->query('sort_direction', 'asc');
        $perPage = (int) $request->query('per_page', 15);

        $stores = $this->storeService->getAllStores($search, $sortBy, $sortDirection, $perPage);

        return response()->json($stores);
    }

    /**
     * Get active stores
     *
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        $stores = $this->storeService->getActiveStores();

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    /**
     * Get stores for authenticated user
     *
     * @return JsonResponse
     */
    public function userStores(): JsonResponse
    {
        $stores = $this->storeService->getStoresForUser(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $stores,
            'current_store_id' => auth()->user()->current_store_id,
        ]);
    }

    /**
     * Get a specific store
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $store = $this->storeService->findStore($id);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $store->load(['manager', 'users', 'stock']),
        ]);
    }

    /**
     * Create a new store
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:stores,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'is_main' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $store = $this->storeService->createStore($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Store created successfully',
                'data' => $store,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a store
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:stores,code,' . $id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'is_main' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $store = $this->storeService->updateStore($id, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Store updated successfully',
                'data' => $store,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a store
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->storeService->deleteStore($id);

            return response()->json([
                'success' => true,
                'message' => 'Store deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign user to store
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignUser(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,manager,cashier,staff',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->storeService->assignUserToStore(
                $id,
                $request->user_id,
                $request->role,
                $request->is_default ?? false
            );

            return response()->json([
                'success' => true,
                'message' => 'User assigned to store successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove user from store
     *
     * @param int $storeId
     * @param int $userId
     * @return JsonResponse
     */
    public function removeUser(int $storeId, int $userId): JsonResponse
    {
        try {
            $this->storeService->removeUserFromStore($storeId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'User removed from store successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Switch user's current store
     * Pass null or 'null' as id to view all stores (admin only)
     *
     * @param Request $request
     * @param string|null $id
     * @return JsonResponse
     */
    public function switchStore(Request $request, ?string $id = null): JsonResponse
    {
        try {
            $user = auth()->user();

            // Si id est "null" ou vide, c'est pour voir tous les stores
            if ($id === 'null' || $id === '' || $id === null) {
                // Vérifier que l'utilisateur est admin
                if (!$user->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas les droits pour voir tous les magasins',
                    ], 403);
                }

                // Mettre à null pour voir tous les stores
                $user->update(['current_store_id' => null]);

                // Forcer la re-authentification
                auth()->setUser($user->fresh());

                return response()->json([
                    'success' => true,
                    'message' => 'Affichage de tous les magasins',
                    'current_store_id' => null,
                ]);
            }

            // Convertir en int pour un store spécifique
            $storeId = (int) $id;
            $this->storeService->switchUserStore(auth()->id(), $storeId);

            // Forcer la re-authentification
            auth()->setUser(auth()->user()->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Store switched successfully',
                'current_store_id' => $storeId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get store stock
     *
     * @param int $id
     * @return JsonResponse
     */
    public function stock(int $id): JsonResponse
    {
        $store = $this->storeService->findStore($id);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        }

        $stock = $store->stock()->with('variant.product')->get();

        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }
}
