<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Inscription, Connexion, OTP"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Inscription d'un nouvel utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","username","phone_number","password"},
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="phone_number", type="string", example="+221701234567"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Inscription réussie. Veuillez vérifier votre téléphone pour le code OTP."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Créer l'utilisateur
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        // Générer et envoyer le code OTP
        $this->sendOtpToPhone($request->phone_number);

        return response()->json([
            'message' => 'Inscription réussie. Veuillez vérifier votre téléphone pour le code OTP.',
            'user' => $user
        ], 201);
    }

    /**
     * Envoyer un code OTP à un numéro de téléphone
     */
    private function sendOtpToPhone($phoneNumber)
    {
        // Générer un code OTP aléatoire de 4 chiffres
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Supprimer les anciens codes OTP pour ce numéro
        OtpCode::where('phone_number', $phoneNumber)->delete();
        
        // Créer un nouveau code OTP
        OtpCode::create([
            'phone_number' => $phoneNumber,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return $code;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/send-otp",
     *     summary="Envoyer un code OTP",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number"},
     *             @OA\Property(property="phone_number", type="string", example="+221701234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP envoyé avec succès"
     *     )
     * )
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $code = $this->sendOtpToPhone($phoneNumber);

        // En production, ici on enverrait le SMS
        // Pour le développement, on retourne le code pour faciliter les tests
        return response()->json([
            'message' => 'Code OTP envoyé avec succès',
            'code' => $code // Toujours retourner le code pour les tests
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     summary="Vérifier le code OTP",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number","code"},
     *             @OA\Property(property="phone_number", type="string", example="+221701234567"),
     *             @OA\Property(property="code", type="string", example="1111")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP vérifié avec succès"
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $otpCode = OtpCode::where('phone_number', $request->phone_number)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$otpCode) {
            return response()->json([
                'message' => 'Code OTP invalide ou expiré'
            ], 400);
        }

        // Marquer le code comme utilisé
        $otpCode->update(['used' => true]);

        // Vérifier le téléphone de l'utilisateur
        $user = User::where('phone_number', $request->phone_number)->first();
        if ($user) {
            $user->update(['phone_verified_at' => now()]);
            
            // Générer un token après la vérification OTP
            $token = $user->createToken('auth-token')->accessToken;
        }

        return response()->json([
            'message' => 'Code OTP vérifié avec succès',
            'user' => $user,
            'token' => $token ?? null
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Connexion utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Vérifier si le téléphone est vérifié
        if (!$user->phone_verified_at) {
            return response()->json([
                'message' => 'Veuillez vérifier votre numéro de téléphone avant de vous connecter',
                'phone_not_verified' => true
            ], 403);
        }

        $token = $user->createToken('auth-token')->accessToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     tags={"Authentication"},
     *     summary="Récupérer l'utilisateur actuel",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
} 