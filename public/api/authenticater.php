<?php
use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Prüft die Zugangsdaten und erstellt ein JWT für die Anmeldung.
 */
class Authenticator
{

    #[OAT\Post(
        path: '/api/v1/authenticate',
        summary: 'Wird Anhand vom Benutzernamen und Passwort authentifizert',
        tags: ['auth'],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'Das JSON muss im Body username und password als String enthalten',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'username',
                        type: 'string',
                        example: 'Martin'
                    ),
                    new OAT\Property(
                        property: 'password',
                        type: 'string',
                        example: 'hAlLoWelT'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Authentifizierung erfolgreich. Cookie ist für eine Stunde gültig.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Authentifizierung nicht erfolgreich. Benutzername oder Passwort ist falsch.'
            )
        ]
    )]

    /**
     * Vergleicht die Zugangsdaten und gibt bei Erfolg ein Token-Cookie zurück.
     *
     * @param Request $request Die eingehende HTTP-Anfrage.
     * @param Response $response Die ausgehende HTTP-Antwort.
     * @param array $args Die Parameter aus dem URL-Pfad.
     * @return Response Die Antwort mit dem Ergebnis der Anmeldung.
     */
    public static function authenticate(Request $request, Response $response, $args)
    {
        global $config;
        $requestBody = $request->getParsedBody();

        // Benutzername und Passwort mit der Konfiguration vergleichen.
        if ($requestBody["username"] != $config["username"] || $requestBody["password"] != $config["password"]) {
            
            return $response->withStatus(401, "Invalid credentials");
        }

        //Generiert ein token das eine Stunde gültig ist.
        $token = Token::create($config["username"], $config["password"], time() + 3600, "localhost");
        //gibt das token als cookie zurück
        setcookie("token", $token, time() + 3600);

        $response = $response->withHeader("content-type", "application/json");
        $response->getBody()->write(json_encode(["success" => true]));
        return $response->withStatus(200);
    }

}