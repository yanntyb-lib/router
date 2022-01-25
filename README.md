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

To make a groupe of route need to check a route 


```mermaid
graph TD
        A["$router = new Router()"] --> B{"$router->addRoute(<br>$name,<br> $path,<br> [Controller::class, 'methode name']<br>OR<br>callable<br>)"}
        A --> H["Modify default template"] --> I["->setAccessDeniedRoutesAJAX($path_to_route)"]

        T["check groupe permission"] --> U["path = /admin/route<br>If groupe is definied whith base path /admin<br>It will check $route_to_check before accessing the route<br>If this last one return true, then we can access to route<br>If not then 403 error is return or Route with $permission_denied_path"] --> V["handle url"] --> G["$router->handleQuerry"]
        T --> W["->noPermission()"] --> V

        B --> routeToCheck --> C["->routeToCheck($path_of_route)"] --> T
        B --> onlyAjax --> D["->isAjax()"] --> T
        B --> eitherAjaxOrNot --> E["->noCheckHeader()"] --> T
        B --> T
        
        C --> routeToCheckReturnFalse --> F["->defaultRoute($path_to_route)"]
        
        F --> M["defaultAccessDeniedRoute"]
        
        M --> R["Not Ajax"] --> DOMAccessDeniedTemplate --> T
        M --> S["Ajax"] --> ajaxAccessDeniedTemplate --> T
        
        H --> J["->setAccessDeniedRoutesDOM($path_to_route)"]
        H --> K["->setDefaultRouteDOM($path_to_route)"]
        H --> L["->setDefaultRouteAJAX($path_to_route)"]
        
       
        A --> X["groupe permission"] --> Q["$router->makeGroupedPermission(<br>$base_route_path,<br>$route_to_check_path,<br>$permission_denied_path<br> OR<br> nothing if you want to use default 403 error)"]

```