<?php

declare(strict_types=1);

namespace LeonWbr\Inertia\Middleware;

use LeonWbr\Inertia\Support\Header;
use LeonWbr\Inertia\Traits\InertiaTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InertiaMiddleware implements MiddlewareInterface
{
  use InertiaTrait;

  protected string $rootView = 'app';

  protected ?ExtensionConfiguration $extensionConfiguration = null;

  public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
  {
    $this->extensionConfiguration = $extensionConfiguration;
  }

  public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler,
  ): ResponseInterface {
    $this->inertia = $this->inertia->fromRequest($request);
    $this->inertia->setVersion($this->version($request));
    $this->inertia->share($this->share($request));
    $this->inertia->setRootView($this->rootView);

    if (!$request->hasHeader(Header::INERTIA)) {
      return $handler->handle($request)->withHeader('Vary', Header::INERTIA);
    }

    $request = $request->withAttribute('inertia', $this->inertia);
    $request = $request->withQueryParams(["type" => "inertia"] + $request->getQueryParams());
    $response = $handler->handle($request);

    if (
      $request->getMethod() === 'GET'
      && $request->hasHeader(Header::VERSION)
      && $request->getHeader(Header::VERSION)[0] !== $this->inertia->getVersion()
    ) {
      $response = $this->onVersionChange($request);
    }

    if ($response->getStatusCode() === 200 && empty($response->getBody())) {
      $response = $this->onEmptyResponse($request);
    }

    if ($response->getStatusCode() === 302 && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'])) {
      $response->withStatus(303);
    }

    return $response;
  }

  public function version(): ?string
  {
    $extensionConfiguration = $this->extensionConfiguration->get('inertia');

    $manifestPath = $extensionConfiguration['manifestPath'] ?? "null";
    if (str_starts_with($manifestPath, 'EXT:')) {
      $manifestPath = GeneralUtility::getFileAbsFileName($manifestPath);
    } else {
      $manifestPath = Environment::getPublicPath() . '/' . $manifestPath;
    }

    if (!empty($manifestPath) && is_file($manifestPath)) {
      return hash_file('xxh128', $manifestPath);
    }

    return null;
  }

  /** @todo Inertia::always resolveValidationErrors */
  public function share(ServerRequestInterface $request): array
  {
    return [];
  }

  /** 
   * @todo Check if TYPO3 stores the previous URL in the session.
   */
  public function onEmptyResponse(
    ServerRequestInterface $request
  ): ResponseInterface {
    $url = $request->getHeader('referer')[0] ?? '/';
    return new RedirectResponse($url);
  }

  public function onVersionChange(
    ServerRequestInterface $request,
  ): ResponseInterface {
    /** @todo Rewrite for TYPO3 */
    // if ($request->hasSession()) {
    //   $request->session()->reflash();
    // }

    return $this->inertia->location((string) $request->getUri());
  }
}
