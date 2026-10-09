<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;

/**
 * Erstellt oder aktualisiert ein Produkt anhand seiner SKU.
 */
class CreateUpdateProductController
{

    #[OAT\Put(
        path: '/api/v1/product/{sku}',
        summary: 'Erstellt oder aktualisiert ein Produkt anhand seiner SKU.',
        tags: ['product'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Sku des Produkts',
                schema: new OAT\Schema(
                    type: 'string',
                    example: '12345678'
                )
            )
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'Alles ausser sku muss mitgesendet werden',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'id_category',
                        type: 'integer',
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        example: 'CsBe Logo'
                    ),
                    new OAT\Property(
                        property: 'image',
                        type: 'string',
                        example: 'https://www.csbe.ch/resources/themes/csbe/images/logo.svg?m=1595406300'
                    ),
                    new OAT\Property(
                        property: 'discription',
                        type: 'string',
                        example: 'Kaufen sie das Logo'
                    ),
                    new OAT\Property(
                        property: 'price',
                        type: 'double',
                        example: '20.20'
                    ),
                    new OAT\Property(
                        property: 'stock',
                        type: 'integer',
                        example: '3'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Produkt erfolgreich aktualisiert.'
            ),
            new OAT\Response(
                response: 201,
                description: 'Produkt erfolgreich erstellt.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige SKU, Produktdaten oder Kategorie.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Keine gültige Authentifizierung.'
            )
        ]
    )]

    /**
     * Prüft die Anmeldung und Eingaben und speichert die Produktdaten.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Die Produktdaten oder eine Fehlermeldung.
     */
    public static function createUpdateProduct(Request $request, Response $response, $args)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        // Die SKU aus dem URL-Pfad übernehmen.
        $skuFromUrl = $args["sku"];

        // Die SKU auf 1 bis 100 Zeichen prüfen.
        if (
            mb_strlen($skuFromUrl, "UTF-8") < 1 ||
            mb_strlen($skuFromUrl, "UTF-8") > 100
        ) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültige SKU in der URL"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $requestData = json_decode((string) $request->getBody(), true);

        // Prüfen, ob diese vier Pflichtfelder vorhanden und nicht null sind.
        if (
            !isset(
                $requestData['active'],
                $requestData['name'],
                $requestData['price'],
                $requestData['stock']
        )
        ) {
            $response->getBody()->write(json_encode(
                ["error" => "JSON pflichtfelder fehlen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Werte übernehmen und für optionale Felder Standardwerte verwenden.
        $sku = $skuFromUrl;
        $name = trim($requestData['name']);
        $active = $requestData['active'];
        $idCategory = $requestData['id_category'] ?? null;
        $image = $requestData['image'] ?? "";
        $description = $requestData['description'] ?? "";
        $price = $requestData['price'];
        $stock = $requestData['stock'];

        // Die Kategorie-ID muss eine Ganzzahl oder null sein.
        if (!is_int($idCategory) && $idCategory !== null) {
            $response->getBody()->write(json_encode(
                ["error" => "Muss eine nummer sein"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Eine mitgesendete Kategorie in der Datenbank suchen.
        if ($idCategory !== null) {
            $statement = $database->prepare("SELECT * FROM category WHERE category_id = ?");
            $statement->execute([$idCategory]);

            if (mysqli_num_rows($statement->get_result()) == 0) {
                $response->getBody()->write(json_encode(
                    ["error" => "category do not exist :("]
                ));
                return $response
                    ->withStatus(404)
                    ->withHeader("Content-Type", "application/json");
            }
        }


        if ($image !== null) {
            $image = trim($image);
        }

        if ($description !== null) {
            $description = trim($description);
        }

        // Werte ausserhalb des Bereichs von 0 bis 1 ablehnen.
        if ($active > 1 || $active < 0) {
            $response->getBody()->write(json_encode(
                ["error" => "active muss 0 oder 1 sein"]
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

        // Die Länge des Bildwerts auf maximal 1000 Zeichen prüfen.
        if ($image !== null && mb_strlen($image, "UTF-8") > 1000) {
            $response->getBody()->write(json_encode(
                ["error" => "Image darf maximal 1000 Zeichen enthalten"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Eine angegebene Kategorie-ID auf den positiven INTEGER-Bereich prüfen.
        if (
            $idCategory !== null &&
            ($idCategory < 1 || $idCategory > 2147483647)
        ) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültige Kategorie-ID"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Preis entsprechend prüfen.
        if ($price < 0 || $price >= 1e63 || round($price, 2) != $price) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültiger Preis oder mehr als zwei Nachkommastellen"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Den Lagerbestand in einem vorgegebenen Bereich prüfen.
        if ($stock < 0 || $stock > 2147483647) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültiger Lagerbestand"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Eine angegebene Kategorie muss existieren.
        if ($idCategory !== null) {
            $statement = $database->prepare(
                "SELECT category_id FROM category WHERE category_id = ?"
            );
            $statement->execute([$idCategory]);

            $result = $statement->get_result();
            $category = $result->fetch_assoc();

            if ($category === null) {
                $response->getBody()->write(json_encode(
                    ["error" => "Die angegebene Kategorie existiert nicht"]
                ));

                return $response
                    ->withStatus(400)
                    ->withHeader("Content-Type", "application/json");
            }
        }

        // Das Produkt anhand seiner SKU suchen.
        $statement = $database->prepare(
            "SELECT product_id FROM product WHERE sku = ?"
        );
        $statement->execute([$skuFromUrl]);

        $result = $statement->get_result();
        $product = $result->fetch_assoc();

        if ($product === null) {
            // Neues Produkt erstellen. product_id wird automatisch vergeben.
            $statement = $database->prepare(
                "INSERT INTO product
                (sku, active, id_category, name, image, description, price, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $statement->execute([
                $sku,
                $active,
                $idCategory,
                $name,
                $image,
                $description,
                $price,
                $stock
            ]);

            $productId = $database->insert_id;
            $status = 201;
        } else {
            // Das gefundene Produkt aktualisieren.
            $productId = (int) $product["product_id"];

            $statement = $database->prepare(
                "UPDATE product
                SET sku = ?, active = ?, id_category = ?, name = ?,
                    image = ?, description = ?, price = ?, stock = ?
                WHERE product_id = ?"
            );

            $statement->execute([
                $sku,
                $active,
                $idCategory,
                $name,
                $image,
                $description,
                $price,
                $stock,
                $productId
            ]);

            $status = 200;
        }

        // Die gespeicherten Produktdaten zurückgeben.
        $response->getBody()->write(json_encode([
            "product_id" => $productId,
            "sku" => $sku,
            "active" => $active,
            "id_category" => $idCategory,
            "name" => $name,
            "image" => $image,
            "description" => $description,
            "price" => $price,
            "stock" => $stock
        ]));

        return $response
            ->withStatus($status)
            ->withHeader("Content-Type", "application/json");
    }
}


