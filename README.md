# dirlist index.php file
```
<?php
use App\Bootstrap\AppManager;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

if (isset($_GET['showdir'])) {
  setCookie('showdir', true, time() + ((@$_GET['showdir']?'1':'-1') * 3600 * 24 * 365));
  header('Location: ' . (@$_SERVER['REQUEST_SCHEME']?:'http') . '://' . $_SERVER['HTTP_HOST']);
} elseif (!@$_COOKIE['showdir'] || isset($_GET['phpinfo'])) {
  phpinfo();
} elseif (@$_COOKIE['showdir']) {
  require_once __DIR__ . '/app/vendor/autoload.php';

  // Set file access restrictions
  ini_set('open_basedir', __DIR__);

  // Initialize environment variable handler
  Dotenv::createImmutable(__DIR__)->safeLoad();

  // Initialize the application
  $app = (new ContainerBuilder)->addDefinitions(
      __DIR__ . '/app/config/app.php',
      __DIR__ . '/app/config/container.php'
  )->build()->call(AppManager::class);

  // Engage!
  $app->run();
}
```
