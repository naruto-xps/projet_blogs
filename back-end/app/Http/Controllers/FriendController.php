<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Amis",
 *     description="Ajout, confirmation, suppression et blocage d'amis"
 * )
 */
class FriendController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users/search",
     *     summary="Rechercher des utilisateurs",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs trouvés"
     *     )
     * )
     */
    public function searchUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->input('query');
        $currentUser = $request->user();

        $users = User::where('id', '!=', $currentUser->id)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('full_name', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->select('id', 'username', 'full_name', 'phone_number')
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/request",
     *     summary="Envoyer une demande d'amitié",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande d'amitié envoyée avec succès"
     *     )
     * )
     */
    public function sendFriendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $friendId = $request->friend_id;

        if ($user->id === $friendId) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous ajouter vous-même comme ami'
            ], 400);
        }

        // Vérifier si une relation existe déjà
        $existingFriendship = DB::table('friendships')
            ->where(function ($query) use ($user, $friendId) {
                $query->where('user_id', $user->id)
                      ->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId) {
                $query->where('user_id', $friendId)
                      ->where('friend_id', $user->id);
            })
            ->first();

        if ($existingFriendship) {
            return response()->json([
                'message' => 'Une relation d\'amitié existe déjà'
            ], 400);
        }

        // Créer la demande d'amitié
        DB::table('friendships')->insert([
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Demande d\'amitié envoyée avec succès'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/friends",
     *     summary="Récupérer la liste des amis",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des amis"
     *     )
     * )
     */
    public function getFriends(Request $request)
    {
        $user = $request->user();
        
        $friends = $user->friends()->select('id', 'username', 'full_name')->get();
        $pendingRequests = $user->pendingFriendRequests()->select('id', 'username', 'full_name')->get();
        $receivedRequests = $user->receivedFriendRequests()->select('id', 'username', 'full_name')->get();

        return response()->json([
            'friends' => $friends,
            'pending_requests' => $pendingRequests,
            'received_requests' => $receivedRequests
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/accept",
     *     summary="Accepter une demande d'amitié",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande d'amitié acceptée"
     *     )
     * )
     */
    public function acceptFriendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $friendId = $request->friend_id;

        $friendship = DB::table('friendships')
            ->where('user_id', $friendId)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'message' => 'Demande d\'amitié non trouvée'
            ], 404);
        }

        DB::table('friendships')
            ->where('user_id', $friendId)
            ->where('friend_id', $user->id)
            ->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Demande d\'amitié acceptée avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/reject",
     *     summary="Rejeter une demande d'amitié",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande d'amitié rejetée"
     *     )
     * )
     */
    public function rejectFriendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $friendId = $request->friend_id;

        DB::table('friendships')
            ->where('user_id', $friendId)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->delete();

        return response()->json([
            'message' => 'Demande d\'amitié rejetée avec succès'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/friends/{friend_id}",
     *     summary="Supprimer un ami",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="friend_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ami supprimé avec succès"
     *     )
     * )
     */
    public function removeFriend(Request $request, $friendId)
    {
        $user = $request->user();

        DB::table('friendships')
            ->where(function ($query) use ($user, $friendId) {
                $query->where('user_id', $user->id)
                      ->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId) {
                $query->where('user_id', $friendId)
                      ->where('friend_id', $user->id);
            })
            ->delete();

        return response()->json([
            'message' => 'Ami supprimé avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/block",
     *     summary="Bloquer un utilisateur",
     *     tags={"Amis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur bloqué avec succès"
     *     )
     * )
     */
    public function blockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $friendId = $request->friend_id;

        if ($user->id === $friendId) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous bloquer vous-même'
            ], 400);
        }

        // Supprimer toute relation existante
        DB::table('friendships')
            ->where(function ($query) use ($user, $friendId) {
                $query->where('user_id', $user->id)
                      ->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId) {
                $query->where('user_id', $friendId)
                      ->where('friend_id', $user->id);
            })
            ->delete();

        // Créer la relation bloquée
        DB::table('friendships')->insert([
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'status' => 'blocked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Utilisateur bloqué avec succès'
        ]);
    }
} 