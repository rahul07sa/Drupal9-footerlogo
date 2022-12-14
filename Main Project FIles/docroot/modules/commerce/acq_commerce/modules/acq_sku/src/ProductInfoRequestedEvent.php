<?php

namespace Drupal\acq_sku;

use Drupal\acq_commerce\SKUInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class Product Info Requested Event.
 *
 * @package Drupal\acq_sku
 */
class ProductInfoRequestedEvent extends Event {

  public const EVENT_NAME = 'product_info_requested';

  /**
   * SKU Entity.
   *
   * @var \Drupal\acq_commerce\SKUInterface
   */
  private $sku;

  /**
   * Field code for which value is requested.
   *
   * @var string
   */
  private $fieldCode;

  /**
   * Context - pdp / plp / basket.
   *
   * @var string
   */
  private $context;

  /**
   * Default value.
   *
   * @var mixed
   */
  private $value;

  /**
   * Flag to specify if value is modified by any event or not.
   *
   * @var bool
   */
  private $valueModified;

  /**
   * ProductInfoRequestedEvent constructor.
   *
   * @param \Drupal\acq_commerce\SKUInterface $sku
   *   SKU Entity.
   * @param string $field_code
   *   Field code for which value is requested.
   * @param string $context
   *   Context - pdp / plp / basket.
   * @param mixed $value
   *   Default value.
   */
  public function __construct(SKUInterface $sku, string $field_code, string $context, $value) {
    $this->sku = $sku;
    $this->fieldCode = $field_code;
    $this->value = $value;
    $this->context = $context;
    $this->valueModified = FALSE;
  }

  /**
   * Get SKU Entity.
   *
   * @return \Drupal\acq_commerce\SKUInterface
   *   SKU Entity.
   */
  public function getSku() {
    return $this->sku;
  }

  /**
   * Get field code for which value is requested.
   *
   * @return string
   *   Field Code.
   */
  public function getFieldCode() {
    return $this->fieldCode;
  }

  /**
   * Get context.
   *
   * @return string
   *   Context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Get current value - default or modified.
   *
   * @return mixed
   *   Current value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Update value.
   *
   * @param mixed $value
   *   Updated value.
   */
  public function setValue($value) {
    $this->valueModified = TRUE;
    $this->value = $value;
  }

  /**
   * Check if value is modified already by any subscriber.
   *
   * @return bool
   *   TRUE if value is modified already by any subscriber.
   */
  public function isValueModified() {
    return $this->valueModified;
  }

}
