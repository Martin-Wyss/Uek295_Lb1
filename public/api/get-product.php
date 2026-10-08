<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;

/**
 * Liest ein Produkt anhand seiner SKU.
 */
class GetProductController
{

    #[OAT\Get(
        path: '/api/v1/product/{sku}',
        summary: 'Das Produkt von der angegebenen sku wird aufgerufen',
        tags: ['product'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'sku des gewünschten Produkts',
                schema: new OAT\Schema(
                    type: 'string',
                    example: '12345678'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Produkt gefunden.'
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
     * Prüft die Anmeldung und SKU und gibt das Produkt zurück.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Das Produkt oder eine Fehlermeldung.
     */
    public static function getProduct(Request $request, Response $response, $args)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        $sku = trim($args["sku"]);

        // Die SKU auf 1 bis 100 Zeichen prüfen.
        if (mb_strlen($sku, "UTF-8") < 1 || mb_strlen($sku, "UTF-8") > 100) {
            $response->getBody()->write(json_encode(
                ["error" => "SKU muss zwischen 1 und 100 Zeichen enthalten"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("SELECT * FROM product WHERE sku = ?");
        $statement->execute([$sku]);

        $result = $statement->get_result();
        $product = $result->fetch_assoc();

        // Ohne Treffer mit Status 404 antworten.
        if ($product === null) {
            $response->getBody()->write(json_encode(
                ["error" => "Produkt nicht gefunden"]
            ));

            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        $response->getBody()->write(json_encode($product));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }


}