# router

$router = new Router(new Route("users", "/",[UserController::class,"showPage"]),true);

To call a methode with parameter juste surround the exact name of the variable with {}
$router->addRoute("articles","/article/{id}",[ArticleController::class, "showArticleById"]);

$router->handleQuery();