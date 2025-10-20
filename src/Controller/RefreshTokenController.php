<?php

namespace App\Controller;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/token/refresh',
            openapi: new Model\Operation(
                responses: [
                    '200' => [
                        'description' => 'Token rafraîchi avec succès',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'token' => ['type' => 'string'],
                                        'refresh_token' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'refresh_token' => ['type' => 'string'],
                                ],
                                'required' => ['refresh_token'],
                            ],
                        ],
                    ])
                )
            ),
            description: 'Rafraîchir le token JWT',
            input: false,
            output: false,
            name: 'api_refresh_token'
        ),
    ]
)]
class RefreshTokenController extends AbstractController
{
    #[Route('/api/token/refresh', name: 'api_refresh_token', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        // Cette méthode ne sera jamais appelée car gérée par le firewall
        throw new \LogicException('This method should not be reached.');
    }
}
