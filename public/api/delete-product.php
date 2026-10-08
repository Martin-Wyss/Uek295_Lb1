<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;

/**
 * Löscht ein Produkt anhand seiner SKU.
 */
class DeleteProductController
{

    #[OAT\Delete(
        path: '/api/v1/product/{sku}',
        summary: 'Das Produkt von der angegebenen sku wird gelöscht',
        tags: ['product'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'sku des zu löschenden Produkts',
                schema: new OAT\Schema(
                    type: 'string',
                    example: '12345678'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 204,
                description: 'Produkt gelöscht.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige sku.'
            ),
            new OAT\Response(
                response: 401,
                description: 'keine Authentifizierung.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Produkt nicht gefunden.'
            )
        ]
    )]

    /**
     * Prüft die Anmeldung und SKU und löscht das Produkt.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Eine leere Erfolgsantwort oder eine Fehlermeldung.
     */
    public static function deleteProduct(Request $request, Response $response, $args)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        // SKU aus der URL auslesen und prüfen.
        $sku = trim($args["sku"]);

        if (mb_strlen($sku, "UTF-8") < 1 || mb_strlen($sku, "UTF-8") > 100) {
            $response->getBody()->write(json_encode(
                ["error" => "SKU muss zwischen 1 und 100 Zeichen enthalten"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("DELETE FROM product WHERE sku = ?");
        $statement->execute([$sku]);


        // Ohne gelöschte Zeile war das Produkt nicht vorhanden.
        if ($statement->affected_rows === 0) {
            $response->getBody()->write(json_encode(
                ["error" => "Produkt nicht gefunden"]
            ));

            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        return $response->withStatus(204);
    }


}