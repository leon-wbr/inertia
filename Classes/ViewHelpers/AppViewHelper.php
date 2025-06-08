<?php

namespace LeonWbr\Inertia\ViewHelpers;

use LeonWbr\Inertia\Traits\InertiaTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** 
 * @todo Reimplement and test. 
 * @see LeonWbr\Inertia\Service\InertiaService->render()
 * @see ../../Resources/Private/Templates/App.html
 */
final class AppViewHelper extends AbstractViewHelper
{
  use InertiaTrait;

  protected $escapeOutput = false;

  public function render()
  {
    $request = null;
    if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
      $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }

    $this->getInertiaFromRequest($request);

    $id = $this->inertia->getRootView();
    $page = [];

    return '<div id="' . $id . '" data-page="' . json_encode($page, JSON_THROW_ON_ERROR) . '"></div>';
  }
}
