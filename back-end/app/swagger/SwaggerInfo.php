<?php

namespace App\swagger;

/**
 * @OA\Info(
 *     title="API Blog Personnel - ISEP_P5",
 *     version="1.0.0",
 *     description="Cette API permet la gestion d'un blog personnel : authentification par OTP, gestion des articles, des amis et des commentaires.",
 *     @OA\Contact(
 *         email="support@isep.sn",
 *         name="Équipe ISEP"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur local (dev)"
 * )
 *
 * @OA\Server(
 *     url="https://api.monblog.isep.sn",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Authentification",
 *     description="Inscription, Connexion, OTP"
 * )
 *
 * @OA\Tag(
 *     name="Articles",
 *     description="Création, modification, suppression, visibilité et publication programmée"
 * )
 *
 * @OA\Tag(
 *     name="Commentaires",
 *     description="Commentaires sur les articles"
 * )
 *
 * @OA\Tag(
 *     name="Amis",
 *     description="Ajout, confirmation, suppression et blocage d'amis"
 * )
 *
 * @OA\Tag(
 *     name="Utilisateurs",
 *     description="Recherche d'utilisateurs, profil"
 * )
 */
class SwaggerInfo {}
