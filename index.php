<?php
require_once "controller/ProductController.php";
require_once "controller/CategoryController.php";
require_once "controller/Controller.php";
require_once "controller/AuthController.php";
require_once "router/Router.php";
require_once "middleware.php";

$router = new Router();
$productController = new ProductController();
$categoryController = new CategoryController();
$authController = new AuthController();


$controller = new Controller();

$router->addMiddleware('logRequest');


$router->addRoute("/", [$controller, "index"]);
$router->addRoute("/products", [$productController, "index2"]);

$router->addRoute("/admin", [$controller, "admin"], ['checkLogin', 'checkAdmin']);

//product
$router->addRoute("/admin/products", [$productController, "index"], ['checkLogin', 'checkUserOrAdmin']);
$router->addRoute("/products/detail/{id}", [$productController, "show"]);
$router->addRoute("/admin/products/create", [$productController, "create"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/products/edit/{id}", [$productController, "edit"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/products/delete/{id}", [$productController, "delete"], ['checkLogin', 'checkAdmin']);


// Route tìm kiếm sản phẩm
$router->addRoute("/search-suggestions", [$productController, "searchSuggestions"]);


//user
$router->addRoute("/admin/users", [$authController, "indexUser"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/users/delete/{id}", [$authController, "delete"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/users/edit/{id}", [$authController, "editRole"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/profile/update/{id}", [$authController, "updateProfile"], ['checkLogin', 'checkUserOrAdmin']);

//category
$router->addRoute("/admin/category", [$categoryController, "index"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/category/create", [$categoryController, "create"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/category/edit/{id}", [$categoryController, "edit"], ['checkLogin', 'checkAdmin']);
$router->addRoute("/admin/category/delete/{id}", [$categoryController, "delete"], ['checkLogin', 'checkAdmin']);

//usser
$router->addRoute("/login", [$authController, "login"]);
$router->addRoute("/register", [$authController, "register"]);
$router->addRoute("/logout", [$authController, "logout"]);
$router->addRoute("/", [$controller, "index"]);
$router->addRoute("/profile/{id}", [$authController, "show"], ['checkLogin', 'checkUserOrAdmin']);
$router->addRoute("/login/google", [$authController, "redirectToGoogle"]);
$router->addRoute("/auth/google-login", [$authController, "googleCallback"]);

//forgot password
$router->addRoute("/forgot-password", [$authController, "forgotPassword"]);
$router->addRoute("/reset-password", [$authController, "resetPassword"]);



$router->addRoute("/unauthorized", [$authController, "unauthorized"]);

$router->dispatch();
?>