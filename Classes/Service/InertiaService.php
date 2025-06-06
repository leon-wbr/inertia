<?php

declare(strict_types=1);

namespace LeonWbr\Inertia\Service;

use LeonWbr\Inertia\Support\Header;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class InertiaService
{
  protected string $rootView = 'app';
  protected array $sharedProps = [];
  protected ?string $version = null;
  protected ?ServerRequestInterface $request = null;

  public function fromRequest(?ServerRequestInterface $request = null): self
  {
    $this->request = $request;

    if (empty($request)) {
      return $this;
    }

    $inertia = $request->getAttribute('inertia');
    if ($inertia instanceof self) {
      return $inertia;
    }

    return $this;
  }

  public function setRootView(string $view): void
  {
    $this->rootView = $view;
  }

  public function getRootView(): string
  {
    return $this->rootView;
  }

  public function share(string|array $key, $value = null): void
  {
    if (is_array($key)) {
      $this->sharedProps = array_merge($this->sharedProps, $key);
    } else {
      $this->sharedProps[$key] = $value;
    }
  }

  public function getShared(?string $key = null, mixed $default = null): array
  {
    if ($key) {
      return $this->sharedProps[$key] ?? $default;
    }

    return $this->sharedProps;
  }

  public function flushShared(): void
  {
    $this->sharedProps = [];
  }

  public function setVersion(?string $version): void
  {
    $this->version = $version;
  }

  public function getVersion(): ?string
  {
    return $this->version;
  }

  public function render(string $component, array $props = []): ResponseInterface
  {
    $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
    $streamFactory = GeneralUtility::makeInstance(StreamFactory::class);

    $data = [
      'component' => $component,
      'props' => array_merge($this->sharedProps, $props),
      'version' => $this->version,
      'rootView' => $this->rootView,
      'url' => $this->request ? $this->request->getUri()->getPath() : '',
    ];

    if ($this->request->hasHeader(Header::INERTIA)) {
      return $responseFactory->createResponse()
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withHeader('Vary', Header::INERTIA)
        ->withHeader(HEADER::INERTIA, 'true')
        ->withBody(
          $streamFactory->createStream(json_encode($data, JSON_THROW_ON_ERROR))
        );
    }

    $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
    $config = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

    $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
    $viewData = new ViewFactoryData(
      templateRootPaths: $config['view']['templateRootPaths'] ?? [],
      request: $this->request,
    );

    $view = $viewFactory->create($viewData);
    $view->assignMultiple([
      'pageData' => $data,
      'rootView' => $this->rootView,
      'title' => $component,
    ]);

    return $responseFactory->createResponse(200)
      ->withHeader('Content-Type', 'text/html; charset=utf-8')
      ->withBody(
        $streamFactory->createStream($view->render($this->rootView))
      );
  }
}
