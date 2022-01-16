# router

$router = new Router(new Route("users", "/",[UserController::class,"showPage"]),true);<br><br><br>



To call a methode with parameter juste surround the exact name of the variable with {}:<br>

$router->addRoute("articles","/article/{id}",[ArticleController::class, "showArticleById"]);<br><br><br>



To add a route to check (the route need to return a bool) before accesing the route:<br>

$router->addRoute("admin connection", "/admin/co", [AdminController::class, "connect"]);<br>
$router->addRoute("admin check connection", "/admin/checklog", [AdminController::class, "checkLog"]);<br>
$router->addRoute("admin page", "/admin/home", [AdminController::class, "home"], "/admin/checklog","/admin/co");<br>

If last parameter is empty then if the route to check return false the default route of the Router will be called

$router->handleQuery();