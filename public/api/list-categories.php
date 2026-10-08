<?php

use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use OpenApi\Attributes as OAT;
class ListCategoriesController
{

    #[OAT\Get(
        path: '/api/v1/categories',
        summary: 'Listet alle Kategorien auf',
        tags: ['list-cat'],
        
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Listet alle Kategorien auf'
            ),      
            new OAT\Response(
                response: 401,
                description: 'keine Authentifizierung.'
            ),           
        ]
    )]

    public static function listCategories(Request $request, Response $response) {

        global $config;
        global $database;

        $token = $_COOKIE["token"] ?? "";

        if ($token == "" || !Token::validate($token, $config['password'])) {
            return $response->withStatus(401);
        }


        $statement = $database->prepare("SELECT * FROM category");
        $statement->execute();

        $result = $statement->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);

        $response->getBody()->write(json_encode($categories));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }


}