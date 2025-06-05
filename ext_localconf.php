<?php

declare(strict_types=1);

use LeonWbr\Inertia\Controller\MainController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
  'Inertia',
  'Renderer',
  [MainController::class => 'index'],
  [MainController::class => 'index'],
  ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
