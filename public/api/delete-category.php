<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;
class DeleteCategoryController
{

    #[OAT\Delete(
        path: '/api/v1/category/{category_id}',
        summary: 'Die Kategorie von der angegebenen ID wird gelöscht',
        tags: ['delete-cat'],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID der gewünschten Kategorie',
                schema: new OAT\Schema(
                    type: 'integer',
                    example: '1'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 204,
                description: 'Kategorie gelöscht.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Kategorie ID.'
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

    public static function deleteCategory(Request $request, Response $response, $args)
    {
        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }

        $categoryId = $args["category_id"];

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

        $statement = $database->prepare("DELETE FROM category WHERE category_id = ?");
        $statement->bind_param("i", $categoryId);
        $statement->execute();



        if ($statement->affected_rows === 0) {
            $response->getBody()->write(json_encode(
                ["error" => "Kategorie nicht gefunden"]
            ));

            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        return $response->withStatus(204);
    }


}