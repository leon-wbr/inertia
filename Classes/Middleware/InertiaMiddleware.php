<?php

declare(strict_types=1);

namespace LeonWbr\Inertia\Middleware;

use LeonWbr\Inertia\Service\InertiaService;
use LeonWbr\Inertia\Support\Header;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InertiaMiddleware implements MiddlewareInterface
{
  protected string $rootView = 'app';

  public function __construct(
    protected InertiaService $inertia,
    protected readonly ExtensionConfiguration $extensionConfiguration,
  ) {}

  public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler,
  ): ResponseInterface {
    if (!$request->hasHeader(Header::INERTIA)) {
      return $handler->handle($request)->withHeader('Vary', Header::INERTIA);
    }

    $this->inertia = $this->inertia->fromRequest($request);
    $this->inertia->setVersion($this->version($request));
    $this->inertia->share($this->share($request));
    $this->inertia->setRootView($this->rootView);

    $request = $request->withAttribute('inertia', $this->inertia);
    $request = $request->withQueryParams(["type" => "inertia"] + $request->getQueryParams());
    $response = $handler->handle($request);

    /** @todo Implement onVersionChange */
    // if ($request->getMethod() === 'GET' && $request->hasHeader(Header::VERSION) && $request->getHeader(Header::VERSION)[0] === $this->inertia->getVersion()) {
    //   $response = $this->onVersionChange($request, $response);
    // }

    /** @todo Implement onEmptyResponse */
    // if ($response->getStatusCode() === 200 && empty($response->getBody())) {
    //   $response = $this->onEmptyResponse($request, $response);
    // }

    if ($response->getStatusCode() === 302 && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'])) {
      $response->withStatus(303);
    }

    return $response;
  }

  public function version()
  {
    $extensionConfiguration = $this->extensionConfiguration->get('inertia');

    $manifestPath = $extensionConfiguration['manifestPath'] ?? null;
    $manifestPath = GeneralUtility::getFileAbsFileName($manifestPath);

    if (!empty($manifestPath) && is_file($manifestPath)) {
      return hash_file('xxh128', $manifestPath);
    }

    return null;
  }

  /** @todo Inertia::always resolveValidationErrors */
  public function share(ServerRequestInterface $request)
  {
    return [
      'SharedProp' => 'Example Shared Value',
    ];
  }
}
