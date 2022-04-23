<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Middleware;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\ExceptionTrap;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Error handling middleware.
 *
 * Traps exceptions and converts them into HTML or content-type appropriate
 * error pages using the CakePHP ExceptionRenderer.
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration values.
     *
     * Ignored if contructor is passed an ErrorHandler instance.
     *
     * - `log` Enable logging of exceptions.
     * - `skipLog` List of exceptions to skip logging. Exceptions that
     *   extend one of the listed exceptions will also not be logged. Example:
     *
     *   ```
     *   'skipLog' => ['Cake\Error\NotFoundException', 'Cake\Error\UnauthorizedException']
     *   ```
     *
     * - `trace` Should error logs include stack traces?
     * - `exceptionRenderer` The renderer instance or class name to use or a callable factory
     *   which returns a \Cake\Error\ExceptionRendererInterface instance.
     *   Defaults to \Cake\Error\ExceptionRenderer
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'skipLog' => [],
        'log' => true,
        'trace' => false,
        'exceptionRenderer' => WebExceptionRenderer::class,
    ];

    /**
     * ExceptionTrap instance
     *
     * @var \Cake\Error\ExceptionTrap|null
     */
    protected ?ExceptionTrap $exceptionTrap = null;

    /**
     * Constructor
     *
     * @param \Cake\Error\ExceptionTrap|array $config The error handler instance
     *  or config array.
     * @throws \InvalidArgumentException
     */
    public function __construct(ExceptionTrap|array $config = [])
    {
        if (Configure::read('debug')) {
            ini_set('zend.exception_ignore_args', '0');
        }

        if (is_array($config)) {
            $this->setConfig($config);

            return;
        }

        $this->exceptionTrap = $config;
    }

    /**
     * Wrap the remaining middleware with error handling.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (RedirectException $exception) {
            return $this->handleRedirect($exception);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }
    }

    /**
     * Handle an exception and generate an error response
     *
     * @param \Throwable $exception The exception to handle.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $trap = $this->getExceptionTrap();
        $trap->logException($exception, $request);

        $renderer = $trap->renderer($exception, $request);

        try {
            /** @var \Psr\Http\Message\ResponseInterface|string $response */
            $response = $renderer->render();
            if (is_string($response)) {
                return new Response(['body' => $response, 'status' => 500]);
            }

            return $response;
        } catch (Throwable $internalException) {
            $trap->logException($internalException, $request);

            return $this->handleInternalError();
        }
    }

    /**
     * Convert a redirect exception into a response.
     *
     * @param \Cake\Http\Exception\RedirectException $exception The exception to handle
     * @return \Psr\Http\Message\ResponseInterface Response created from the redirect.
     */
    public function handleRedirect(RedirectException $exception): ResponseInterface
    {
        return new RedirectResponse(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Handle internal errors.
     *
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    protected function handleInternalError(): ResponseInterface
    {
        return new Response([
            'body' => 'An Internal Server Error Occurred',
            'status' => 500,
        ]);
    }

    /**
     * Get a exception trap instance
     *
     * @return \Cake\Error\ExceptionTrap The exception trap.
     */
    protected function getExceptionTrap(): ExceptionTrap
    {
        if ($this->exceptionTrap === null) {
            /** @var class-string<\Cake\Error\ExceptionTrap> $className */
            $className = App::className('ExceptionTrap', 'Error');
            $this->exceptionTrap = new $className($this->getConfig());
        }

        return $this->exceptionTrap;
    }
}
