<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OAT;

// Titel und Version der API für Swagger festlegen.
#[OAT\Info(
    title: "Meine API ÜK LB1",
    version: "1.0.0"
)]

/**
 * Enthält den Haupt-Endpoint der API.
 */
class ApiMain
{
    /**
     * Gibt eine Begrüssung zurück.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Die Antwort mit der Begrüssung.
     */
    public static function index(Request $request, Response $response, $args)
    {
        $response->getBody()->write("Hello, world!");
        return $response;
    }
}