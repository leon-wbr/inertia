<?php

declare(strict_types=1);

namespace LeonWbr\Inertia\Service;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class InertiaContentElementRenderer
{
  public function __construct(private readonly ContentObjectRenderer $cObj) {}

  public function renderRecord(array $record, ?array $allowProperties = [], bool $useCamelCase = true): array
  {
    $conf = [
      'tables' => 'tt_content',
      'source' => $record['uid'],
    ];

    $html = $this->cObj->cObjGetSingle('RECORDS', $conf);
    $data = empty($allowProperties) ? $record : array_intersect_key($record, array_flip($allowProperties));

    if ($useCamelCase) {
      $data = $this->convertKeysToCamelCase($data);
    }

    return [
      'CType' => $record['CType'],
      'data' => $data,
      'html' => $html,
    ];
  }

  private function convertKeysToCamelCase(array $array): array
  {
    $result = [];
    foreach ($array as $key => $value) {
      $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
      $result[$camelKey] = is_array($value) ? $this->convertKeysToCamelCase($value) : $value;
    }
    return $result;
  }
}
