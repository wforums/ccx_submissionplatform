<?php
use Katzgrau\KLogger\Logger;
use org\ccextractor\submissionplatform\containers\AccountManager;
use org\ccextractor\submissionplatform\containers\BotDatabaseLayer;
use org\ccextractor\submissionplatform\containers\DatabaseLayer;
use org\ccextractor\submissionplatform\containers\EmailLayer;
use org\ccextractor\submissionplatform\containers\FileHandler;
use org\ccextractor\submissionplatform\containers\FTPConnector;
use org\ccextractor\submissionplatform\containers\GitWrapper;
use org\ccextractor\submissionplatform\containers\TemplateValues;
use org\ccextractor\submissionplatform\controllers\AccountController;
use org\ccextractor\submissionplatform\controllers\BaseController;
use org\ccextractor\submissionplatform\controllers\GitBotController;
use org\ccextractor\submissionplatform\controllers\HomeController;
use org\ccextractor\submissionplatform\controllers\IController;
use org\ccextractor\submissionplatform\controllers\SampleInfoController;
use org\ccextractor\submissionplatform\controllers\TestController;
use org\ccextractor\submissionplatform\controllers\UploadController;
use Slim\App;
use Slim\Container;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // FIXME: replace with session middleware

include_once '../src/configuration.php';
include_once '../src/bot-configuration.php';
require '../vendor/autoload.php';

$container = new Container();

// Slim app
$app = new App($container);

// Add CSRF protection middleware
$container['csrf'] = function ($c) {
    return new Guard();
};

$app->add($container->get('csrf'));

// Twig
$container['view'] = function ($c) {
    $view = new Twig('../src/templates', [
        /*'cache' => '../twig_cache',*/
        'strict_variables' => true,
        'autoescape' => true,
        'debug' => true
    ]);

    // Instantiate and add Slim specific extension
    $view->addExtension(new TwigExtension(
        $c['router'],
        $c['request']->getUri()
    ));

    $view->getEnvironment()->addExtension(new Twig_Extensions_Extension_I18n());

    return $view;
};

// Database
$dba = new DatabaseLayer(DATABASE_SOURCE_NAME, DATABASE_USERNAME, DATABASE_PASSWORD);
$container->register($dba);
// Bot Database
$bdba = new BotDatabaseLayer(BOT_DATABASE_SOURCE_NAME, BOT_DATABASE_USERNAME, BOT_DATABASE_PASSWORD);
$container->register($bdba);
// Email container
$host = $app->environment["HTTP_HOST"];
BaseController::$BASE_URL = (($app->environment["HTTPS"] === "on")?"https://":"http://").$app->environment["HTTP_HOST"];
$email = new EmailLayer(AMAZON_SES_USER, AMAZON_SES_PASS, $host);
$container->register($email);
// GitHub API
$github = new GitWrapper();
$container->register($github);
// Account Manager
$account = new AccountManager($dba,$email,HMAC_KEY);
$container->register($account);
// Template Values
$templateValues = new TemplateValues();
$container->register($templateValues);
// FTP Connector
$ftp = new FTPConnector($app->environment["HTTP_HOST"], 21, $dba);
$container->register($ftp);
// File Handler
$file_handler = new FileHandler($dba, TEMP_STORAGE, PERM_STORAGE);
$container->register($file_handler);
// Logger (non added right now)
$logger = new Logger(__DIR__."/../private/logs"); // FUTURE: create wrapper layer for slim

//Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        /** @var TemplateValues $tv */
        $tv = $c->get('templateValues');
        $tv->add('pageName','404 Not Found');
        $tv->add('pageDescription','404 Not Found');
        $tv->add('isLoggedIn',$c->get('account')->isLoggedIn());
        $tv->add("loggedInUser", $c->get('account')->getUser());
        return $c->get('view')->render($response->withStatus(404),"notfound.html.twig",$tv->getValues());
    };
};

$pages = [
    new HomeController(),
    new SampleInfoController(),
    new UploadController(),
    new TestController(),
    // new TestSuiteController(),
    new AccountController()
];

// FUTURE: add global middleware: session, authentication, ...

$templateValues->add("pages",$pages);

// Define routes

/** @var IController $page */
foreach($pages as $page){
    $page->register($app);
}
// These stay out of the regular pages, but need to be registered anyway
$gitBotController = new GitBotController(
    $bdba, BOT_CCX_VBOX_MANAGER, BOT_CCX_WORKER, __DIR__."/reports", $logger, BOT_AUTHOR, BOT_REPOSITORY_FOLDER,
    BOT_HMAC_KEY, BOT_WORKER_URL);
$gitBotController->register($app);

$app->run();