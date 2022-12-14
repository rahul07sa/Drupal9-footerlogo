<?php

namespace Drupal\acq_cart\Plugin\Block;

use Drupal\acq_cart\CartStorageInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CartBlock' block.
 *
 * @Block(
 *   id = "cart_block",
 *   admin_label = @Translation("Cart block"),
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\acq_cart\CartStorageInterface definition.
   *
   * @var \Drupal\acq_cart\CartStorageInterface
   */
  protected $cartStorage;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\acq_cart\CartStorageInterface $cart_storage
   *   The cart session.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartStorageInterface $cart_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cartStorage = $cart_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('acq_cart.cart_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cart = $this->cartStorage->getCart();
    $items = $cart->items();

    $rows = [];

    foreach ($items as $item) {
      $rows[$item['item_id']] = [$item['name'], $item['qty'], $item['price']];
    }

    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Name'), $this->t('Quantity'), $this->t('Price')],
      '#empty' => $this->t('There are no products in your cart yet.'),
      '#rows' => $rows,
    ];

    $totals = $cart->totals();

    $build['#rows']['sub'] = ['', $this->t('Subtotal'), $totals['sub']];

    if ((float) $totals['tax'] > 0) {
      $build['#rows']['tax'] = ['', $this->t('Tax'), $totals['tax']];
    }

    if ((float) $totals['discount'] > 0) {
      $build['#rows']['discount'] = [
        '',
        $this->t('Discount'),
        $totals['discount'],
      ];
    }

    $build['#rows']['grand'] = ['', $this->t('Grand Total'), $totals['grand']];

    return $build;
  }

}
