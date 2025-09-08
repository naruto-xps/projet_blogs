<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Articles",
 *     description="Création, modification, suppression, visibilité et publication programmée"
 * )
 */
class ArticleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/articles",
     *     summary="Récupérer les articles du fil d'actualité",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des articles",
     *         @OA\JsonContent(
     *             @OA\Property(property="articles", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Récupérer les amis de l'utilisateur
        $friendIds = $user->friends()->pluck('users.id');
        
        // Récupérer les articles publics des amis et de l'utilisateur
        $articles = Article::with(['user', 'comments.user'])
            ->where(function ($query) use ($user, $friendIds) {
                $query->where('user_id', $user->id)
                      ->orWhereIn('user_id', $friendIds);
            })
            ->where('is_public', true)
            ->published()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'articles' => $articles
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/articles/my",
     *     summary="Récupérer mes articles",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste de mes articles"
     *     )
     * )
     */
    public function myArticles(Request $request)
    {
        $articles = $request->user()
            ->articles()
            ->with(['comments.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'articles' => $articles
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/articles",
     *     summary="Créer un nouvel article",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content"},
     *             @OA\Property(property="title", type="string", example="Mon premier article"),
     *             @OA\Property(property="content", type="string", example="Contenu de l'article..."),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="allow_comments", type="boolean", example=true),
     *             @OA\Property(property="published_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Article créé avec succès"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_public' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $article = $request->user()->articles()->create([
            'title' => $request->title,
            'content' => $request->content,
            'is_public' => $request->get('is_public', true),
            'allow_comments' => $request->get('allow_comments', true),
            'published_at' => $request->published_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Article créé avec succès',
            'article' => $article->load('user')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/articles/{id}",
     *     summary="Récupérer un article spécifique",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article récupéré avec succès"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $article = Article::with(['user', 'comments.user'])->find($id);

        if (!$article) {
            return response()->json([
                'message' => 'Article non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur peut voir l'article
        $user = $request->user();
        if ($article->user_id !== $user->id && !$article->is_public) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return response()->json([
            'article' => $article
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/articles/{id}",
     *     summary="Modifier un article",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="is_public", type="boolean"),
     *             @OA\Property(property="allow_comments", type="boolean"),
     *             @OA\Property(property="published_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article modifié avec succès"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'message' => 'Article non trouvé'
            ], 404);
        }

        if ($article->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'is_public' => 'sometimes|boolean',
            'allow_comments' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $article->update($request->only([
            'title', 'content', 'is_public', 'allow_comments', 'published_at'
        ]));

        return response()->json([
            'message' => 'Article modifié avec succès',
            'article' => $article->load('user')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/articles/{id}",
     *     summary="Supprimer un article",
     *     tags={"Articles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article supprimé avec succès"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'message' => 'Article non trouvé'
            ], 404);
        }

        if ($article->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $article->delete();

        return response()->json([
            'message' => 'Article supprimé avec succès'
        ]);
    }
} 