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

Route are not accessible from an ajax call, To reverse it and only make it accessible from ajax:<br>
To make this work, this.req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');<br>
I recommande you tu use a moderne js framework or to use my lib AjaxMaker<br>

$router->addRoute("ajax route", "/foo", [ApiController::class, "foo"])->isAjax();<br>

To make a Route both accessible from AJAX or not:<br><br>
$router->addRoute("admin check connection", "/admin/checklog", [AdminController::class, "checkLog"])->noCheckHeader();<br>
$router->addRoute("ajax route", "/foo", [ApiController::class, "foo"])->isAjax()->routeToCheck("/admin/check");<br>

To set a Route to be access directly after Route call and not taking care about if routeToCheck return true:<br>
$router->addRoute("check formulaire post data","/admin/post/checkData", [AdminController::class, "checkPostData"])->then("/admin/post/showData");
$router->addRoute("afficher post data", "/admin/post/showData", [AdminController::class, "showData"])->routeToCheck("/admin/post/checkData");

To handle the router in the navigator:

$router->handleQuery();

To modifie access denied page : <br>

$router->setAccessDeniedRoutesAJAX(Route);<br>
$router->setAccessDeniedRoutesDOM(Route);<br>

To modifie route not found page :<br>
$router->setDefaultRouteDOM(Route);<br>
$router->setDefaultRouteAJAX(Route);


```mermaid
graph LR
        A["$router = new Router()"] --> B{"$router->addRoute(<br>$name,<br> $path,<br> [Controller::class, 'methode name']<br>OR<br>callable<br>)"}
        A --> H["Modify default template"] --> I["->setAccessDeniedRoutesAJAX($path_to_route)"]
        
        B --> routeToCheck --> C["->routeToCheck($path_of_route)"] --> G["$router->handleQuerry"]
        B --> onlyAjax --> D["->isAjax()"] --> G["$router->handleQuerry"]
        B --> eitherAjaxOrNot --> E["->noCheckHeader()"] --> G["$router->handleQuerry"]
        B --> N
        
        C --> routeToCheckReturnFalse --> F["->defaultRoute($path_to_route)"] --> G["$router->handleQuerry"]
        
        F --> defaultAccessDeniedRoute --> M
        
        M["Not Ajax"] --> DOMAccessDeniedTemplate --> N
        M["Ajax"] --> ajaxAccessDeniedTemplate --> N
        
        H --> J["->setAccessDeniedRoutesDOM($path_to_route)"]
        H --> K["->setDefaultRouteDOM($path_to_route)"]
        H --> L["->setDefaultRouteAJAX($path_to_route)"]
        
        N["handleUrl"] --> G["$router->handleQuerry"]
```