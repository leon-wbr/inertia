<?php

declare(strict_types=1);

namespace LeonWbr\Inertia\Controller;

use LeonWbr\Inertia\Service\InertiaContentElementRenderer;
use LeonWbr\Inertia\Traits\InertiaTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class MainController extends ActionController
{
  use InertiaTrait;

  public function __construct(
    protected readonly ContentObjectRenderer $contentObjectRenderer,
    protected readonly InertiaContentElementRenderer $contentElementRenderer,
    protected readonly PageRepository $pageRepository,
    protected readonly AssetCollector $assetCollector,
  ) {}

  public function indexAction(): ResponseInterface
  {
    $pageArguments = $this->request->getAttribute('routing');
    $pageId = $pageArguments->getPageId();
    $page = $this->pageRepository->getPage($pageId);

    $contentElements = $this->contentObjectRenderer->getRecords(
      'tt_content',
      [
        'pidInList' => $pageId,
        'orderBy' => 'sorting',
      ]
    );

    foreach ($contentElements as &$contentElement) {
      $contentElement = $this->contentElementRenderer->renderRecord($contentElement);
    }

    $this->assetCollector->addInlineJavaScript(
      'vite-refresh',
      "import RefreshRuntime from 'http://localhost:5173/@react-refresh'
        RefreshRuntime.injectIntoGlobalHook(window)
        window.\$RefreshReg\$ = () => {}
        window.\$RefreshSig\$ = () => (type) => type
        window.__vite_plugin_react_preamble_installed__ = true",
      [
        'type' => 'module',
      ],
      [
        'priority' => true,
      ]
    );

    $this->assetCollector->addJavaScript(
      'vite-client',
      'http://localhost:5173/@vite/client',
      [
        'type' => 'module',
      ]
    );

    $this->assetCollector->addJavaScript(
      'vite-main',
      'http://localhost:5173/src/main.tsx',
      [
        'type' => 'module',
      ]
    );

    return $this->inertia->render('Default', [
      'title' => $page['title'] ?? null,
      'message' => 'Welcome to the Inertia Site Package!',
      'contentElements' => $contentElements,
    ]);
  }
}
