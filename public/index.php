<?php

use Slim\Factory\AppFactory;

// Composer-Pakete und API-Klassen laden.
require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/api/api-main.php";
require_once __DIR__ . "/api/authenticater.php";
require_once __DIR__ . "/api/create-category.php";
require_once __DIR__ . "/api/get-category.php";
require_once __DIR__ . "/api/delete-category.php";
require_once __DIR__ . "/api/list-categories.php";
require_once __DIR__ . "/api/update-category.php";
require_once __DIR__ . "/api/create-update-product.php";
require_once __DIR__ . "/api/get-product.php";
require_once __DIR__ . "/api/delete-product.php";
require_once __DIR__ . "/api/list-products.php";


$config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);

// Die Slim-Anwendung erstellen.
$app = AppFactory::create();

// Den gemeinsamen Basispfad aller API-Routen festlegen.
$app->setBasePath("/api/v1");

// Request-Bodies automatisch einlesen lassen.
$app->addBodyParsingMiddleware();

// Die Verbindung zur MySQL-Datenbank herstellen.
$database = new mysqli("localhost", "root", "", "uek295_lb01");

// Die Route für die Anmeldung registrieren.
$app->post("/authenticate", [Authenticator::class, "authenticate"]);

// Die Route zum Erstellen einer Kategorie registrieren.
$app->post("/category", [CreateCategoryController::class, "createCategory"]);

// Eine Kategorie anhand ihrer ID lesen.
$app->get("/category/{category_id}", [GetCategoryController::class, "getCategory"]);

// Eine Kategorie anhand ihrer ID löschen.
$app->delete("/category/{category_id}", [DeleteCategoryController::class, "deleteCategory"]);

// Alle Kategorien auflisten.
$app->get("/categories", [ListCategoriesController::class, "listCategories"]);

// Eine Kategorie anhand ihrer ID aktualisieren.
$app->patch("/category/{category_id}", [UpdateCategoryController::class, "updateCategory"]);

// Ein Produkt anhand seiner SKU erstellen oder aktualisieren.
$app->put("/product/{sku}", [CreateUpdateProductController::class, "createUpdateProduct"]);

// Ein Produkt anhand seiner SKU lesen.
$app->get("/product/{sku}", [GetProductController::class, "getProduct"]);

// Ein Produkt anhand seiner SKU löschen.
$app->delete("/product/{sku}", [DeleteProductController::class, "deleteProduct"]);

// Alle Produkte auflisten.
$app->get("/products", [ListProductsController::class, "listProducts"]);

// Die Anfrage von Slim verarbeiten lassen.
$app->run();