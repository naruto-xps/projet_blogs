<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Commentaires",
 *     description="Commentaires sur les articles"
 * )
 */
class CommentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/articles/{article_id}/comments",
     *     summary="Ajouter un commentaire à un article",
     *     tags={"Commentaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="article_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Excellent article !")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Commentaire ajouté avec succès"
     *     )
     * )
     */
    public function store(Request $request, $articleId)
    {
        $article = Article::find($articleId);

        if (!$article) {
            return response()->json([
                'message' => 'Article non trouvé'
            ], 404);
        }

        // Vérifier si l'article permet les commentaires
        if (!$article->allow_comments) {
            return response()->json([
                'message' => 'Les commentaires ne sont pas autorisés sur cet article'
            ], 403);
        }

        // Vérifier si l'utilisateur peut voir l'article
        $user = $request->user();
        if ($article->user_id !== $user->id && !$article->is_public) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = $article->comments()->create([
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté avec succès',
            'comment' => $comment->load('user')
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/comments/{id}",
     *     summary="Supprimer un commentaire",
     *     tags={"Commentaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commentaire supprimé avec succès"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $comment = Comment::with('article')->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Commentaire non trouvé'
            ], 404);
        }

        $user = $request->user();

        // Vérifier si l'utilisateur peut supprimer le commentaire
        // (soit l'auteur du commentaire, soit le propriétaire de l'article)
        if ($comment->user_id !== $user->id && $comment->article->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Commentaire supprimé avec succès'
        ]);
    }
} 