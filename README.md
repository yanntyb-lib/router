# router

$router = new Router(new Route("users", "/",[UserController::class,"showPage"]));

$router->addRoute("articles","/article",[ArticleController::class, "showPage"]);

$query = str_replace("/index.php","",$_SERVER['PHP_SELF']);
$router->handleQuery($query);