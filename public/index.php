<?php

use Slim\Factory\AppFactory;

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/api/api-main.php";
require_once __DIR__ . "/api/authenticater.php";
require_once __DIR__ . "/api/create-category.php";
require_once __DIR__ . "/api/get-category.php";
require_once __DIR__ . "/api/delete-category.php";
require_once __DIR__ . "/api/list-categories.php";
require_once __DIR__ . "/api/update-category.php";
require_once __DIR__ . "/api/create-update-product.php";


$config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);

$app = AppFactory::create();

$app->setBasePath("/api/v1");

$app->addBodyParsingMiddleware();

$database = new mysqli("localhost", "root", "", "uek295_lb01");

$app->post("/authenticate", [authenticater::class, "authenticate"]);

$app->post("/category", [CreateCategoryController::class,"createCategory"]);

$app->get("/category/{category_id}", [GetCategoryController::class,"getCategory"]);

$app->delete("/category/{category_id}", [DeleteCategoryController::class,"deleteCategory"]);

$app->get("/categories", [ListCategoriesController::class,"listCategories"]);

$app->patch("/category/{category_id}", [UpdateCategoryController::class,"updateCategory"]);

$app->put("/product/{sku}", [CreateUpdateProductController::class,"createUpdateProduct"]);

$app->run();