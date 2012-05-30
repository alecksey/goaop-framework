<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2012, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

use Go\Core\Autoload;
use Go\Aop\Support\AdvisorRegistry;
use Go\Aop\Support\DefaultPointcutAdvisor;
use Go\Aop\Support\NameMatchMethodPointcut;
use Go\Aop\Framework\FieldBeforeInterceptor;
use Go\Aop\Framework\ClassFieldAccess;
use Go\Aop\Framework\MethodAfterInterceptor;
use Go\Aop\Framework\MethodBeforeInterceptor;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;

use Go\Instrument\ClassLoading\SourceTransformingLoader;
use Go\Instrument\Transformer\AopProxyTransformer;
use Go\Instrument\Transformer\FilterInjectorTransformer;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src' . PATH_SEPARATOR . __DIR__ . '/../vendor/andrewsville/php-token-reflection');

ini_set('display_errors', true);

include '../src/Go/Core/Autoload.php';
Autoload::init();

/*********************************************************************************
 *                             ASPECT BLOCK
**********************************************************************************/

$pointcut = new NameMatchMethodPointcut();
$pointcut->setMappedName('*');

$before = new MethodBeforeInterceptor(function(MethodInvocation $invocation) {
    $obj = $invocation->getThis();
    echo 'Calling Before Interceptor for method: ',
         is_object($obj) ? get_class($obj) : $obj,
         $invocation->getMethod()->isStatic() ? '::' : '->',
         $invocation->getMethod()->getName(),
         '()',
         ' with arguments: ',
         json_encode($invocation->getArguments()),
         "<br>\n";
}, $pointcut);

$after = new MethodAfterInterceptor(function(MethodInvocation $invocation) {
    $obj = $invocation->getThis();
    echo 'Calling After Interceptor for method: ',
         is_object($obj) ? get_class($obj) : $obj,
         $invocation->getMethod()->isStatic() ? '::' : '->',
         $invocation->getMethod()->getName(),
         '()',
         ' with arguments: ',
         json_encode($invocation->getArguments()),
         "<br>\n";
}, $pointcut);


$beforeAdvisor = new DefaultPointcutAdvisor($pointcut, $before);
$afterAdvisor  = new DefaultPointcutAdvisor($pointcut, $after);

AdvisorRegistry::register($beforeAdvisor);
AdvisorRegistry::register($afterAdvisor);

/*********************************************************************************
 *                             CONFIGURATION FOR TRANSFORMERS BLOCK
**********************************************************************************/
SourceTransformingLoader::registerFilter();

$sourceTransformers = array(
    new FilterInjectorTransformer(__DIR__, __DIR__, SourceTransformingLoader::getId()),
    new AopProxyTransformer(
        new TokenReflection\Broker(
            new TokenReflection\Broker\Backend\Memory()
        )
    ),
);

foreach ($sourceTransformers as $sourceTransformer) {
    SourceTransformingLoader::addTransformer($sourceTransformer);
}

/*********************************************************************************
 *                             TEST CODE BLOCK
 * Remark: SourceTransformingLoader::load('app_autoload.php') should be here later
**********************************************************************************/

$class = new Example();
$class->hello('Welcome!');

echo "=========================================<br>\n";

$class = new ExampleField();
$class->hello('welcome');