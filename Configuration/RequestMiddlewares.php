<?php

use LeonWbr\Inertia\Middleware\InertiaMiddleware;

return [
  'frontend' => [
    'inertia-middleware' => [
      'target' => InertiaMiddleware::class,
      'before' => ['typo3/cms-frontend/page-resolver']
    ],
  ],
];
