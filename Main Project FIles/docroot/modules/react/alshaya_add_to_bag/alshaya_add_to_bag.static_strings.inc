<?php

/**
 * @file
 * Contains Add to Bag related static strings and translations.
 */

/**
 * Add to Bag strings.
 */
function _alshaya_add_to_bag_strings() {
  return [
    [
      'key' => 'in_basket',
      'value' => t('in basket'),
    ],
    [
      'key' => 'size_guide',
      'value' => t('Size Guide'),
    ],
    [
      'key' => 'item_code',
      'value' => t('Item Code'),
    ],
    [
      'key' => 'view_full_product_details',
      'value' => t('View Full Product Details'),
    ],
    [
      'key' => 'quantity',
      'value' => t('Quantity'),
    ],
    [
      'key' => 'view_options',
      'value' => t('view options'),
    ],
    [
      'key' => 'quick_view',
      'value' => t('Quick View'),
    ],
    [
      'key' => 'add_to_cart',
      'value' => t('add to cart'),
    ],
    [
      'key' => 'item_added',
      'value' => t('Item added'),
    ],
    [
      'key' => 'purchase_limit_error_msg',
      'value' => t('Purchase limit has been reached'),
    ],
    [
      'key' => 'view_more',
      'value' => t('view more'),
    ],
  ];
}

/**
 * AddToBag translations.
 */
function _alshaya_add_to_bag_translations() {
  $strings = [
    'v1' => [
      'view options' => [
        'ar' => 'عرض الخيارات',
      ],
      'Quick View' => [
        'ar' => 'نظرة سريعة',
      ],
      'Item added' => [
        'ar' => 'تمت إضافة العنصر',
      ],
      'add to cart' => [
        'ar' => 'أضف إلى الحقيبة',
      ],
      'view more' => [
        'ar' => 'عرض التفاصيل',
      ],
    ],
  ];

  return $strings;
}

/**
 * Get translations for given version.
 *
 * @param string $version
 *   Version number like 'v1' etc.
 *
 * @return array
 *   Translations.
 */
function _alshaya_add_to_bag_get_translations(string $version = '') {
  $translations = _alshaya_add_to_bag_translations();
  // If no version mentioned, return all version strings.
  if (empty($version)) {
    $trans_data = [];
    foreach ($translations as $trans) {
      $trans_data = array_merge($trans_data, $trans);
    }

    return $trans_data;
  }

  return $translations[$version] ?? [];
}
