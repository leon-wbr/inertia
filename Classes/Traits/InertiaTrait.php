<?php

namespace LeonWbr\Inertia\Traits;

use LeonWbr\Inertia\Service\InertiaService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * NOTE: While this trait was a cool idea, it is not used in the current implementation.
 * It does not work as intended since accessing the request automatically like this is rarely (if ever) possible.
 */
trait InertiaTrait
{
  protected ?InertiaService $inertia = null;

  public function injectInertia(InertiaService $inertia): void
  {
    if (!empty($this->inertia)) {
      return;
    }

    if (property_exists($this, 'request') && !empty($this->request)) {
      $request = $this->request;
    }

    $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
    $this->inertia = $inertia->fromRequest($request);
  }

  public function getInertiaFromRequest(ServerRequestInterface $request): ?InertiaService
  {
    return $request->getAttribute('inertia') ?? $this->injectInertia(GeneralUtility::makeInstance(InertiaService::class));
  }
}
