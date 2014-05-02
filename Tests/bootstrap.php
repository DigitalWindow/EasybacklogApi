<?php

require_once $_SERVER['GUZZLE'] . '/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Guzzle' => $_SERVER['GUZZLE'] . '/src',
    'Guzzle\\tests' => $_SERVER['GUZZLE'] . '/tests',
));
$loader->register();

// spl_autoload_register(function($class) {
//     if (0 === strpos($class, 'Guzzle\\Unfuddle\\')) {
//         $path = implode('/', array_slice(explode('\\', $class), 2)) . '.php';
//         require_once __DIR__ . '/../' . $path;
//         return true;
//     }
// });

\Guzzle\Tests\GuzzleTestCase::setMockBasePath(__DIR__ . DIRECTORY_SEPARATOR . 'mock');
// \Guzzle\tests\GuzzleTestCase::setServiceBuilder(\Guzzle\Service\ServiceBuilder::factory(array(
//     'test.easybacklogapi' => array(
//         'class' => 'Guzzle.Unfuddle.UnfuddleClient',
//         'params' => array(
//             'username' => 'test_user',
//             'password' => '****',
//             'subdomain' => 'test'
//         )
//     )
// )));