<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;

/**
 * Erstellt eine Kategorie in der Datenbank.
 */
class CreateCategoryController
{

    #[OAT\Post(
        path: '/api/v1/category',
        summary: 'Es wird eine neue Kategorie erstellt.',
        tags: ['category'],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'Die Kategorie benötigt active und name.',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        example: '1'
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Firmen-Logos'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 201,
                description: 'Kategorie erfolgreich erstellt.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Falsche eingabe.'
            ),
            new OAT\Response(
                response: 401,
                description: 'keine Authentifizierung.'
            )
        ]
    )]

    /**
     * Prüft die Anmeldung und Eingaben und speichert eine neue Kategorie.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @return Response Die Antwort oder eine Fehlermeldung.
     */
    public static function createCategory(Request $request, Response $response)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        $statement = $database->prepare("INSERT INTO category (active, name) VALUES (?, ?)");

        $request_data = json_decode((string) $request->getBody(), true);

        // Prüfen, ob beide Pflichtfelder vorhanden und nicht null sind.
        if (!isset($request_data['name'], $request_data['active'])) {
            $response->getBody()->write(json_encode(
                ["error" => "JSON pflichtfelder fehlen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $name = trim($request_data['name']);
        $active = $request_data['active'];

        // Werte ausserhalb des Bereichs von 0 bis 1 ablehnen.
        if ($active > 1 || $active < 0) {
            $response->getBody()->write(json_encode(
                ["error" => "Keine gültige nummer!"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Die Namenslänge auf 1 bis 500 Zeichen prüfen.
        if (strlen($name) > 500 || strlen($name) < 1) {
            $response->getBody()->write(json_encode(
                ["error" => "Kein Name oder zu viele Zeichen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement->execute([$active, $name]);

        $response->getBody()->write(json_encode(
            ["success" => "Wurde erstellt"]
        ));
        return $response
            ->withStatus(201)
            ->withHeader("Content-Type", "application/json");
    }
}