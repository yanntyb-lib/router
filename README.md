# router

$router = new Router(new Route("home", "/",[HomeController::class,"home"]),true);<br><br><br>

To call a methode with parameter juste surround the exact name of the variable with {}:<br>

$router->addRoute("articles","/article/{id}",[ArticleController::class, "showArticleById"]);<br><br><br>

To add a route to check (the route need to return a bool) before accesing the route:<br>

$router->addRoute("admin connection", "/admin/connection", [AdminController::class, "connect"]);<br>
$router->addRoute("admin check connection", "/admin/checklog", [AdminController::class, "checkLog"]);<br>
$router->addRoute("admin page", "/admin/home", [AdminController::class, "home"])<br>
        ->routeToCheck("/admin/checklog")<br>
        ->defaultRoute("/admin/connection");<br>

If the route to check return false then 403 will be return except if u use ->defaultRoute then it will call this last one

Route are not accessible from an ajax call, To reverse it and only make it accessible from ajax:

$router->addRoute("ajax route", "/foo", [ApiController::class, "foo"])->isAjax();

To handle the router in the navigator:

$router->handleQuery();

To modifie access denied page : <br>

$router->setAccessDeniedRoutesAJAX(Route);<br>
$router->setAccessDeniedRoutesDOM(Route);<br>

To modifie route not found page :<br>
$router->setDefaultRouteDOM(Route);<br>
$router->setDefaultRouteAJAX(Route);
