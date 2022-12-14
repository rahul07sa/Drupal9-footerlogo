<?php

namespace Drupal\acq_checkout\Plugin\CheckoutFlow;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Places an order through a series of steps.
 *
 * Checkout flows are multi-step forms that can be configured by the store
 * administrator. This configuration is stored in the acq_checkout_flow
 * config entity and injected into the plugin at instantiation.
 */
interface CheckoutFlowInterface extends FormInterface, PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the current cart.
   *
   * @return \Drupal\acq_cart\CartInterface
   *   The current cart.
   */
  public function getCart();

  /**
   * Gets the API wrapper.
   *
   * @return \Drupal\acq_commerce\Conductor\APIWrapper
   *   The API wrapper.
   */
  public function getApiWrapper();

  /**
   * Gets the current step ID.
   *
   * @return string
   *   The current step ID.
   */
  public function getStepId();

  /**
   * Gets the previous step ID.
   *
   * Determined based on the position of the current step ID in the list
   * of visible steps.
   *
   * @return string|null
   *   The previous step, or NULL if there is none.
   */
  public function getPreviousStepId();

  /**
   * Gets the next step ID.
   *
   * Determined based on the position of the current step ID in the list
   * of visible steps.
   *
   * @return string|null
   *   The next step ID, or NULL if there is none.
   */
  public function getNextStepId();

  /**
   * Redirects an order to a specific step in the checkout.
   *
   * @param string $step_id
   *   The step ID to redirect to.
   *
   * @throws \Drupal\acq_commerce\Response\NeedsRedirectException
   */
  public function redirectToStep($step_id);

  /**
   * Gets the defined steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   *   Each step definition has the following keys:
   *   - label: The label of the step.
   *   - previous_label: The label shown on the button that returns the
   *                     customer back to this step.
   *   - next_label: The label shown on the button that sends the customer to
   *                 this step.
   *   If the previous_label or next_label keys are missing, the corresponding
   *   buttons will not be shown to the customer.
   */
  public function getSteps();

  /**
   * Gets the visible steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   */
  public function getVisibleSteps();

}
