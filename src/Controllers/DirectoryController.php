<?php

namespace App\Controllers;

use DI\Container;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Contracts\Translation\TranslatorInterface;

class DirectoryController
{
    /** @var Container The application container */
    protected $container;

    /** @var Finder File finder component */
    protected $finder;

    /** @var Twig Twig templating component */
    protected $view;

    /** @var TranslatorInterface Translator component */
    protected $translator;

    /**
     * Create a new IndexController object.
     *
     * @param \DI\Container                                      $container
     * @param \Symfony\Component\Finder\Finder                   $finder
     * @param \Slim\Views\Twig                                   $view
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        Container $container,
        Finder $finder,
        Twig $view,
        TranslatorInterface $translator
    ) {
        $this->container = $container;
        $this->finder = $finder;
        $this->view = $view;
        $this->translator = $translator;
    }

    /**
     * Invoke the IndexController.
     *
     * @param \Slim\Psr7\Request  $request
     * @param \Slim\Psr7\Response $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $err = @$_COOKIE['err'];
        setcookie("err", "", time() - 3600);
        unset($_COOKIE['err']);
        
        if(@$_GET['logout']) {
          setcookie("username", "", time() - 3600);
          unset($_COOKIE['username']);
          header('Location: ./?_=' . rand());
          exit;
        } elseif(isset($_GET['submit'])) {
          $userdetail = $this->container->get('userdetail');
          //echo "<pre>"; print_r($userdetail); echo "123123"; exit;
          if(@$userdetail[@$_GET['username']] === md5(@$_GET['password'])) {
            //header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            setcookie("username", @$_GET['username']);
          } else {
            setcookie("username", "", time() - 3600);
            unset($_COOKIE['username']);
            setcookie("err", "Login fail !", time() + 60);
          }
          header('Location: ./?_=' . rand());
          exit;
        }
        
        $path = $request->getQueryParams()['dir'] ?? '.';

        try {
            $files = $this->finder->in($path)->depth(0);
        } catch (Exception $exception) {
            return $this->view->render($response->withStatus(404), 'error.twig', [
                'message' => $this->translator->trans('error.directory_not_found')
            ]);
        }

        return $this->view->render($response, 'index.twig', [
            'files' => $files,
            'path' => $path,
            'current_link' => str_replace('\\','/',$path),
            'readme' => $this->readme($files),
            'title' => $path == '.' ? 'Home' : $path,
            'err' => $err,
            'username' => @$_GET['username'],
            'password' => @$_GET['password'],
            'is_login' => @$_COOKIE['username'],
        ]);
    }

    /**
     * Return the README file within a finder object.
     *
     * @param \Symfony\Component\Finder\Finder $files
     *
     * @return \Symfony\Component\Finder\SplFileInfo|null
     */
    protected function readme(Finder $files): ?SplFileInfo
    {
        if (! $this->container->get('display_readmes')) {
            return null;
        }

        $readmes = (clone $files)->name('/^README(?:\..+)?$/i');

        $readmes->filter(static function (SplFileInfo $file) {
            return (bool) preg_match('/text\/.+/', mime_content_type($file->getPathname()));
        })->sort(static function (SplFileInfo $file1, SplFileInfo $file2) {
            return $file1->getExtension() <=> $file2->getExtension();
        });

        if (! $readmes->hasResults()) {
            return null;
        }

        return $readmes->getIterator()->current();
    }
}
