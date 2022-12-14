<?php

namespace Drupal\acq_commerce\Conductor;

use Drupal\acq_commerce\APIHelper;
use Drupal\acq_commerce\Connector\ConnectorException;
use Drupal\acq_commerce\Connector\CustomerNotFoundException;
use Drupal\acq_commerce\Event\OrderPlacedEvent;
use Drupal\acq_commerce\I18nHelper;
use Drupal\acq_sku\AcqSkuLinkedSku;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\acq_sku\Entity\SKU;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * API Wrapper class.
 */
class APIWrapper implements APIWrapperInterface {

  use \Drupal\acq_commerce\Conductor\AgentRequestTrait;

  use StringTranslationTrait;

  /**
   * Error code used internally for API Down cases.
   */
  public const API_DOWN_ERROR_CODE = 600;

  /**
   * Store ID.
   *
   * @var mixed
   */
  protected $storeId;

  /**
   * Whether route events are on or not.
   *
   * @var bool
   */
  protected $routeEvents = TRUE;

  /**
   * API Helper service object.
   *
   * @var \Drupal\acq_commerce\APIHelper
   */
  protected $helper;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\acq_commerce\Conductor\ClientFactory $client_factory
   *   ClientFactory object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactoryInterface object.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   LoggerChannelFactory object.
   * @param \Drupal\acq_commerce\I18nHelper $i18nHelper
   *   I18nHelper object.
   * @param \Drupal\acq_commerce\APIHelper $api_helper
   *   API Helper service object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event Dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ClientFactory $client_factory,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactory $logger_factory,
                              I18nHelper $i18nHelper,
                              APIHelper $api_helper,
                              EventDispatcherInterface $dispatcher,
                              LockBackendInterface $lock,
                              ModuleHandlerInterface $module_handler) {
    $this->clientFactory = $client_factory;
    $this->apiVersion = $config_factory->get('acq_commerce.conductor')->get('api_version');
    $this->logger = $logger_factory->get('acq_sku');
    $this->helper = $api_helper;
    $this->dispatcher = $dispatcher;
    $this->lock = $lock;
    $this->moduleHandler = $module_handler;

    // We always use the current language id to get store id. If required
    // function calling the api wrapper will pass different store id to
    // override this one or call $this->updateStoreContext().
    $this->storeId = $i18nHelper->getStoreIdFromLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function updateStoreContext($store_id) {
    // Calling code will be responsible for doing all checks on the value.
    $this->storeId = $store_id;
  }

  /**
   * {@inheritdoc}
   */
  public function createCart($customer_id = NULL) {
    $versionInClosure = $this->apiVersion;
    $endpoint = $this->apiVersion . '/agent/cart/create';

    $doReq = function ($client, $opt) use ($endpoint, $customer_id, $versionInClosure) {
      if (!empty($customer_id)) {
        if ($versionInClosure === 'v1') {
          $opt['form_params']['customer_id'] = $customer_id;
        }
        else {
          $opt['json']['customer_id'] = (string) $customer_id;
        }
      }
      return ($client->post($endpoint, $opt));
    };

    $cart = [];

    try {
      $cart = $this->tryAgentRequest($doReq, 'createCart', 'cart');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function skuStockCheck($sku) {
    $sku = urlencode($sku);
    $endpoint = $this->apiVersion . "/agent/stock/$sku";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    try {
      $message = $this->tryAgentRequest($doReq, 'skuStockCheck', 'stock');

      // Add sku to message to allow processing it the same way as stock push.
      $message['sku'] = $sku;

      return $message;
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCart($cart_id, $customer_id = NULL) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id";

    $doReq = function ($client, $opt) use ($endpoint, $customer_id) {
      if (!empty($customer_id)) {
        $opt['query']['customer_id'] = $customer_id;
      }
      return ($client->get($endpoint, $opt));
    };

    $cart = [];

    try {
      $cart = $this->tryAgentRequest($doReq, 'getCart', 'cart');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function updateCart($cart_id, $cart) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id";

    // Check $item['name'] is a string because in the cart we
    // store name as a 'renderable link object' with a type,
    // a url, and a title. We only want to pass title to the
    // Acquia Commerce Connector.
    // But for robustness we go back to the SKU plugin and ask
    // it to return a name as a string only.
    $originalItemsNames = [];
    $items = $cart->items ?? NULL;
    if ($items) {
      foreach ($items as $key => &$item) {
        $cart->items[$key]['qty'] = (int) $item['qty'];

        if (array_key_exists('name', $item)) {
          if (!isset($item['sku'])) {
            $cart->items[$key]['name'] = "";
            continue;
          }

          $originalItemsNames[$item['sku']] = $item['name'];

          $sku = SKU::loadFromSku($item['sku']);

          if ($sku instanceof SKU) {
            $plugin = $sku->getPluginInstance();
            $cart->items[$key]['name'] = $plugin->cartName($sku, $item, TRUE);
            continue;
          }

          $cart->items[$key]['name'] = "";
        }
      }
    }

    // Clean up cart data.
    $cart = $this->helper->cleanCart($cart);

    $doReq = function ($client, $opt) use ($endpoint, $cart) {
      $opt['json'] = $cart;
      return ($client->post($endpoint, $opt));
    };

    try {
      // First invalidate so even if we get exception, all blocks are updated.
      Cache::invalidateTags(['cart:' . $cart_id]);
      $cart = $this->tryAgentRequest($doReq, 'updateCart', 'cart');
    }
    catch (ConnectorException $e) {
      // Restore cart structure.
      foreach ($cart->items ?? [] as $key => $item) {
        if (isset($originalItemsNames[$item['sku']])) {
          $cart->items[$key]['name'] = $originalItemsNames[$item['sku']];
        }
      }

      // Now throw.
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function associateCart($cart_id, $customer_id) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id/associate";

    $doReq = function ($client, $opt) use ($endpoint, $customer_id, $cart_id) {
      $opt['json'] = [
        'customer_id' => (string) $customer_id,
        'cart_id' => (string) $cart_id,
      ];
      return ($client->post($endpoint, $opt));
    };

    $response = ['success' => 0];

    try {
      $response = $this->tryAgentRequest($doReq, 'associateCart');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function placeOrder($cart_id, $customer_id = NULL) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id/place";

    $doReq = function ($client, $opt) use ($endpoint, $customer_id) {
      if (!empty($customer_id)) {
        $opt['query']['customer_id'] = $customer_id;
      }

      return ($client->post($endpoint, $opt));
    };

    $result = [];

    // Create a name to use for lock acquisition.
    $lock_name = 'place_order_' . $cart_id;
    $locked = FALSE;

    if (Settings::get('acq_commerce_lock_place_order', TRUE) && !($locked = $this->lock->acquire($lock_name))) {
      $this->logger->warning(
        "Could not acquire lock to place order: @lock_name", [
          '@lock_name' => $lock_name,
        ]
      );

      // @codingStandardsIgnoreLine
      throw new RouteException(__FUNCTION__, $this->t('Sorry, we were able to complete your purchase but something went wrong and we could not display the order confirmation page. Please review your past orders or contact our customer service team for assistance.'));
    }

    try {
      $result = $this->tryAgentRequest($doReq, 'placeOrder');

      // If we acquired a lock previously, we can release it now.
      if ($locked) {
        $this->lock->release($lock_name);
      }

      $this->dispatcher->dispatch(OrderPlacedEvent::EVENT_NAME, new OrderPlacedEvent($result, $cart_id));
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingMethods($cart_id) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id/shipping";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $methods = [];

    try {
      $methods = $this->tryAgentRequest($doReq, 'getShippingMethods', 'methods');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingEstimates($cart_id, $address, $customer_id = NULL) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id/estimate";

    // Cart constructor sets cart to any object passed in,
    // circumventing ->setBilling() so trap any wayward extension[] here.
    if (isset($address)) {
      $address = $this->helper->cleanCartAddress($address);
    }

    $doReq = function ($client, $opt) use ($endpoint, $address, $customer_id) {
      $opt['json'] = $address;

      if (!empty($customer_id)) {
        $opt['query']['customer_id'] = $customer_id;
      }

      return ($client->post($endpoint, $opt));
    };

    $methods = [];

    try {
      $methods = $this->tryAgentRequest($doReq, 'getShippingEstimates', 'methods');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethods($cart_id) {
    $endpoint = $this->apiVersion . "/agent/cart/$cart_id/payments";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $methods = [];

    try {
      $methods = $this->tryAgentRequest($doReq, 'getPaymentMethods', 'methods');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function createCustomer(array $customer, $password = NULL) {
    // First check if the user exists in Magento.
    try {
      // Try to get the customer but don't throw exceptions.
      /** @var array $existingCustomer */
      $existingCustomer = $this->getCustomer($customer['email'], FALSE);
      if (!empty($existingCustomer)) {
        $customer['customer_id'] = $existingCustomer['customer_id'];
      }
    }
    catch (\Exception) {
      // Note we catch \Exception instead of RouteException
      // but with the flag set FALSE
      // above, we do not expect an exception here at all unless
      // something went very wrong in $this->getCustomer().
      unset($customer['customer_id']);
    }

    // Second: Update or create customer and return customer.
    // Throws RouteException.
    return $this->updateCustomer($customer, ['password' => $password]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateCustomer($customer, array $options = []) {
    $versionInClosure = $this->apiVersion;
    $endpoint = $this->apiVersion . "/agent/customer";

    $doReq = function ($client, $opt) use ($endpoint, $customer, $options, $versionInClosure) {
      $opt['json']['customer'] = $customer;

      if (isset($options['password']) && !empty($options['password'])) {
        if ($versionInClosure === 'v1') {
          $opt['json']['password'] = $options['password'];
        }
        else {
          $opt['json']['customer']['password'] = $options['password'];
        }
      }

      if (isset($options['password_old']) && !empty($options['password_old'])) {
        $opt['json']['password_old'] = $options['password_old'];
      }

      if (isset($options['password_token']) && !empty($options['password_token'])) {
        $opt['json']['password_token'] = $options['password_token'];
      }

      if (isset($options['access_token']) && !empty($options['access_token'])) {
        $opt['json']['token'] = $options['access_token'];
      }

      // Invoke the alter hook to allow all modules to update the customer data.
      $this->moduleHandler->alter('acq_commerce_update_customer_api_request', $opt);

      // Do some cleanup.
      $opt['json']['customer'] = $this->helper->cleanCustomerData($opt['json']['customer']);

      return ($client->post($endpoint, $opt));
    };

    $customer = [];

    try {
      $customer = $this->tryAgentRequest($doReq, 'updateCustomer', 'customer');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCustomerAddress($customer_id, $address_id) {
    $endpoint = $this->apiVersion . "/agent/customer/address/delete";

    $doReq = function ($client, $opt) use ($endpoint, $customer_id, $address_id) {
      $opt['form_params']['customer_id'] = $customer_id;
      $opt['form_params']['address_id'] = $address_id;
      return ($client->post($endpoint, $opt));
    };

    $deleted = FALSE;

    try {
      $response = $this->tryAgentRequest($doReq, 'deleteCustomerAddress');
      if (isset($response['deleted'])) {
        $deleted = (bool) $response['deleted'];
      }
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function validateCustomerAddress($address) {
    $endpoint = $this->apiVersion . "/agent/customer/address/validate";

    $doReq = function ($client, $opt) use ($endpoint, $address) {
      $opt['json'] = $address;
      return ($client->post($endpoint, $opt));
    };

    $response = [];

    try {
      $response = $this->tryAgentRequest($doReq, 'validateCustomerAddress');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCustomerPassword($email) {
    $endpoint = $this->apiVersion . "/agent/customer/resetpass/get";

    $doReq = function ($client, $opt) use ($endpoint, $email) {
      $opt['form_params']['email'] = $email;

      return ($client->post($endpoint, $opt));
    };

    $success = FALSE;

    try {
      $response = $this->tryAgentRequest($doReq, 'resetCustomerPassword');
      if (isset($response['success'])) {
        $success = (bool) $response['success'];
      }
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticateCustomer($email, $password) {
    $versionInClosure = $this->apiVersion;
    $endpoint = $this->apiVersion . "/agent/customer/$email";

    $doReq = function ($client, $opt) use ($endpoint, $password, $versionInClosure) {
      if ($versionInClosure === 'v1') {
        $opt['form_params']['password'] = $password;
      }
      else {
        $opt['json']['password'] = $password;
      }

      return ($client->post($endpoint, $opt));
    };

    $customer = [];

    try {
      $customer = $this->tryAgentRequest($doReq, 'authenticateCustomer', 'customer');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer($email, $throwCustomerNotFound = TRUE) {
    $endpoint = $this->apiVersion . "/agent/customer/$email";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $customer = [];

    try {
      $customer = $this->tryAgentRequest($doReq, 'getCustomer', 'customer');
      $customer = $this->helper->cleanCustomerData($customer);
    }
    catch (CustomerNotFoundException $e) {
      if ($throwCustomerNotFound) {
        throw $e;
      }
      else {
        // Implies we are testing if a customer email address exists
        // in the ecommerce app.
        // In which case we prevent exceptions being re-thrown.
        // Instead we just return the initial $customer = [].
      }
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage(), $e->getCode(), $e);
    }

    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerOrders($email, $order_id = NULL) {
    $endpoint = $this->apiVersion . "/agent/customer/orders/$email";

    $doReq = function ($client, $opt) use ($endpoint, $order_id) {
      if (!empty($order_id)) {
        $opt['query']['order_id'] = $order_id;
      }
      return ($client->get($endpoint, $opt));
    };

    $orders = [];

    try {
      $orders = $this->tryAgentRequest($doReq, 'getCustomerOrders', 'orders');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $orders;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerToken($email, $password) {
    $endpoint = $this->apiVersion . "/agent/customer/token/get";

    $doReq = function ($client, $opt) use ($endpoint, $email, $password) {
      $opt['form_params']['email'] = $email;
      $opt['form_params']['password'] = $password;

      return ($client->post($endpoint, $opt));
    };

    $customer = [];

    try {
      $customer = $this->tryAgentRequest($doReq, 'getCustomerToken', 'customer');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentCustomer($token = NULL) {
    $endpoint = $this->apiVersion . "/agent/customer-by-token";

    $doReq = function ($client, $opt) use ($endpoint, $token) {
      $opt['form_params']['token'] = $token;
      return ($client->post($endpoint, $opt));
    };

    $customer = [];

    try {
      $customer = $this->tryAgentRequest($doReq, 'getCurrentCustomer', 'customer');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrderStatus($order_id, $status, $comment = '') {
    $endpoint = $this->apiVersion . '/agent/order/' . $order_id;

    $doReq = function ($client, $opt) use ($endpoint, $status, $comment) {
      $opt['json']['status'] = $status;
      $opt['json']['comment'] = $comment;

      return ($client->post($endpoint, $opt));
    };

    try {
      return $this->tryAgentRequest($doReq, 'updateOrderStatus', 'status');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $endpoint = $this->apiVersion . "/agent/categories";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $categories = [];

    // At 20180228 store_id *is* acm_uuid is enforced
    // $acm_uuid is sent in the X-ACM-UUID header
    // It must only be this way:
    $acm_uuid = $this->storeId;
    if (!$acm_uuid) {
      $acm_uuid = "";
    }

    try {
      $categories = $this->tryAgentRequest($doReq, 'getCategories', 'categories', $acm_uuid);
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductOptions() {
    $endpoint = $this->apiVersion . "/agent/product/options";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $options = [];

    $acm_uuid = $this->storeId ?: '';

    try {
      $options = $this->tryAgentRequest($doReq, 'getAttributeOptions', 'options', $acm_uuid);
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPromotions($type = 'category') {
    // As the parameter is used in endpoint path, we restrict it to avoid
    // unexpected exception.
    if (!in_array($type, ['category', 'cart'])) {
      return [];
    }

    $endpoint = $this->apiVersion . "/agent/promotions/$type";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $result = [];

    try {
      $result = $this->tryAgentRequest($doReq, 'getPromotions', 'promotions');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductsByUpdatedDate(\DateTime $date_time) {
    $endpoint = $this->apiVersion . "/agent/products";

    $doReq = function ($client, $opt) use ($endpoint, $date_time) {
      $opt['query']['updated'] = $date_time->format('Y-m-d H:i:s');
      return ($client->get($endpoint, $opt));
    };

    $products = [];

    try {
      $products = $this->tryAgentRequest($doReq, 'getProductsByUpdatedDates', 'products');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $products;
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts($count = 100) {
    $endpoint = $this->apiVersion . "/agent/products";
    $doReq = function ($client, $opt) use ($endpoint, $count) {
      $opt['query']['page_size'] = $count;

      // To allow hmac sign to be verified properly we need them in asc order.
      ksort($opt['query']);

      return ($client->get($endpoint, $opt));
    };

    try {
      return $this->tryAgentRequest($doReq, 'getProducts', 'products', $this->storeId ?? '');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function productFullSync($skus = '', $page_size = 0, $acm_uuid = "", $categoryId = "") {
    $endpoint = $this->apiVersion . "/agent/products";

    $doReq = function ($client, $opt) use ($endpoint, $skus, $categoryId) {

      if (!empty($categoryId)) {
        $opt['query']['category_id'] = $categoryId;
      }
      elseif (!empty($skus)) {
        $opt['query']['skus'] = $skus;
      }

      // To allow hmac sign to be verified properly we need them in asc order.
      // Really?
      ksort($opt['query']);

      return $client->get($endpoint, $opt);
    };

    $products = [];

    try {
      $products = $this->tryAgentRequest($doReq, 'productFullSync', 'products', $skus);
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $products;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentToken($method) {
    $endpoint = $this->apiVersion . "/agent/cart/token/$method";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $result = [];

    try {
      $result = $this->tryAgentRequest($doReq, 'getPaymentToken', 'token');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribeNewsletter($email) {
    $versionInClosure = $this->apiVersion;
    $endpoint = $this->apiVersion . '/agent/newsletter/subscribe';

    $doReq = function ($client, $opt) use ($endpoint, $email, $versionInClosure) {
      if ($versionInClosure === 'v1') {
        $opt['form_params']['email'] = $email;
      }
      else {
        $opt['json']['customer']['email'] = $email;
      }

      return ($client->post($endpoint, $opt));
    };

    try {
      return $this->tryAgentRequest($doReq, 'subscribeNewsletter');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function systemWatchdog() {
    $endpoint = $this->apiVersion . "/agent/system/wd";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $result = [];

    try {
      $result = $this->tryAgentRequest($doReq, 'systemWatchdog');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $result;
  }

  /**
   * Get linked skus for a given sku by linked type.
   *
   * @param string $sku
   *   The sku id.
   * @param string $type
   *   Linked type. Like - related/crosssell/upsell.
   *
   * @return array|mixed
   *   All linked skus of given type.
   *
   * @throws \Drupal\acq_commerce\Conductor\RouteException
   */
  public function getLinkedskus($sku, $type = AcqSkuLinkedSku::LINKED_SKU_TYPE_ALL) {
    $sku = urlencode($sku);
    $endpoint = $this->apiVersion . "/agent/product/$sku/related/$type";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $result = [];

    try {
      $result = $this->tryAgentRequest($doReq, 'linkedSkus', 'related');
    }
    catch (ConnectorException $e) {
      throw new RouteException(__FUNCTION__, $e->getMessage(), $e->getCode(), $this->getRouteEvents());
    }

    return $result;
  }

  /**
   * Gets route event status.
   */
  public function getRouteEvents() {
    return $this->routeEvents;
  }

  /**
   * Turns route events on.
   */
  public function turnRouteEventsOn() {
    $this->routeEvents = TRUE;
  }

  /**
   * Turns route events off.
   */
  public function turnRouteEventsOff() {
    $this->routeEvents = FALSE;
  }

  /**
   * Perform a silent request.
   *
   * Turns off route events and catches all exceptions.
   *
   * @param string $method
   *   The method name.
   * @param array $params
   *   The method params.
   *
   * @return mixed
   *   The API request response.
   *
   * @throws \Exception
   *   Failed request exception.
   */
  public function silentRequest($method, array $params = []) {
    $response = NULL;

    if (!method_exists($this, $method)) {
      throw new \InvalidArgumentException("Method {$method} doesn't exist.");
    }

    $this->turnRouteEventsOff();

    try {
      $response = call_user_func_array([$this, $method], $params);
    }
    catch (\Exception $e) {
      $mesg = sprintf(
        'Silent exception during %s request: (%d) - %s',
        $method,
        $e->getCode(),
        $e->getMessage()
      );

      $this->logger->error($mesg);
    }

    $this->turnRouteEventsOn();

    return $response;
  }

  /**
   * Get position of the products in a category.
   *
   * @param int $category_id
   *   The category id.
   *
   * @return array
   *   All products with the positions in the given category.
   *
   * @throws \Exception
   */
  public function getProductPosition($category_id) {
    $endpoint = $this->apiVersion . "/agent/category/$category_id/position";

    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);

    $result = [];

    try {
      $result = $this->tryAgentRequest($doReq, 'productPosition', 'position');
    }
    catch (ConnectorException $e) {
      throw new \Exception($e->getMessage(), $e->getCode());
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueStatus(): int {
    if ($this->apiVersion != 'v2') {
      $this->logger->error('This feature is available only for v2. Current version is @version', ['@version' => $this->apiVersion]);
      return -1;
    }
    $endpoint = $this->apiVersion . "/agent/queue/total";
    $doReq = fn($client, $opt) => $client->get($endpoint, $opt);
    try {
      $result = $this->tryAgentRequest($doReq, 'getQueueStatus');
    }
    catch (ConnectorException $e) {
      throw new \Exception($e->getMessage(), $e->getCode());
    }
    return (int) $result['total'];
  }

  /**
   * {@inheritdoc}
   */
  public function purgeQueue(): bool {
    if ($this->apiVersion != 'v2') {
      $this->logger->error('This feature is available only for v2.');
      return FALSE;
    }
    $endpoint = $this->apiVersion . "/agent/queue/purge";
    $doReq = fn($client, $opt) => $client->post($endpoint, $opt);
    try {
      $this->tryAgentRequest($doReq, 'purgeQueue');
    }
    catch (ConnectorException) {
      return FALSE;
    }
    return TRUE;
  }

}
