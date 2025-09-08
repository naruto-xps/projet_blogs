<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendController;


// Routes d'authentification
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protégées par authentification
Route::middleware('auth:api')->group(function () {
    // Déconnexion
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Utilisateur actuel
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Articles
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/my', [ArticleController::class, 'myArticles']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
    
    // Commentaires
    Route::get('/articles/{article_id}/comments', [CommentController::class, 'index']);
    Route::post('/articles/{article_id}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    
    // Recherche d'utilisateurs
    Route::get('/users/search', [FriendController::class, 'searchUsers']);
    
    // Gestion des amis
    Route::get('/friends', [FriendController::class, 'getFriends']);
    Route::post('/friends/request', [FriendController::class, 'sendFriendRequest']);
    Route::post('/friends/accept', [FriendController::class, 'acceptFriendRequest']);
    Route::post('/friends/reject', [FriendController::class, 'rejectFriendRequest']);
    Route::delete('/friends/{friend_id}', [FriendController::class, 'removeFriend']);
    Route::post('/friends/block', [FriendController::class, 'blockUser']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
