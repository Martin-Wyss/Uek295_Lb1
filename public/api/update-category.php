<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;

/**
 * Aktualisiert eine Kategorie anhand ihrer ID.
 */
class UpdateCategoryController
{

    #[OAT\Patch(
        path: '/api/v1/category/{category_id}',
        summary: 'Aktualisiert eine Kategorie anhand der ID',
        tags: ['category'],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID von der Kategorie die man aktualisiert',
                schema: new OAT\Schema(
                    type: 'integer',
                    example: '1'
                )
            )
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'active und name der Kategorie',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        example: '2'
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Tolle Firmen Logos'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorie erfolgreich aktualisiert'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Kategorie ID oder Eingabedaten.'
            ),
            new OAT\Response(
                response: 401,
                description: 'keine Authentifizierung.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Kategorie nicht gefunden.'
            )
        ]
    )]


    /**
     * Prüft die Anmeldung und Eingaben und aktualisiert die Kategorie.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Die aktualisierte Kategorie oder eine Fehlermeldung.
     */
    public static function updateCategory(Request $request, Response $response, $args)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        // Die ID auf eine positive Ganzzahl im INTEGER-Bereich prüfen.
        $categoryId = filter_var(
            $args["category_id"],
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 2147483647]]
        );

        if ($categoryId === false) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültige Kategorie ID"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

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


        $statement = $database->prepare("SELECT * FROM category WHERE category_id = ?");
        $statement->execute([$categoryId]);

        $result = $statement->get_result();
        $category = $result->fetch_assoc();

        // Ohne Treffer mit Status 404 antworten.
        if ($category === null) {
            $response->getBody()->write(json_encode(
                ["error" => "Kategorie nicht gefunden"]
            ));

            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("UPDATE category SET active = ?, name = ? WHERE category_id = ?");
        $statement->execute([$active, $name, $categoryId]);

        $response->getBody()->write(json_encode([
            "category_id" => $categoryId,
            "active" => $active,
            "name" => $name
        ]));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }


}