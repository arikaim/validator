<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * Response validator strategy
 */
class ValidatorStrategy implements InvocationStrategyInterface
{
    /**
     * Container ref
     *
     * @var Container|null
     */
    protected $container;

    /**
     * Constructor
     *
     * @param Container|null $container;
     */
    public function __construct($container = null)
    {         
        $this->container = $container;
    }

    /**
     * Invoke a route callable with request, response, Validator with rote parameters.
     * 
     * @param array|callable         $callable
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $routeArguments
     * @return ResponseInterface
    */
    public function __invoke(callable $callable, ServerRequestInterface $request, ResponseInterface $response, array $routeArguments): ResponseInterface  
    {
        foreach ($routeArguments as $key => $value) {          
            $request = $request->withAttribute($key,$value);
        }
        $body = $request->getParsedBody();        
        $data = \array_merge($routeArguments,(\is_array($body) == false) ? [] : $body);
     
        $validator = new Validator(
            $data,
            function() use ($callable) {
                return $callable[0]->getDataValidCallback();
            },
            function() use($callable) {
                return $callable[0]->getValidationErrorCallback();
            }
        );

        return $callable($request,$response,$validator,$routeArguments);
    }
}
