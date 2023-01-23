<?php

namespace Drupal\farm_nfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Forest plan gfw form.
 *
 * @ingroup farm_nfa
 */
class ForestPlanGfwForm extends FormBase {
  
  public function getPlan() {
    return \Drupal::routeMatch()->getRawParameter('plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_nfa_forest_budget_plan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Set the form title.
    $form['#title'] = $this->t('GFW');

    $form['gfw_map'] = [
      '#type' => 'farm_map',
      '#map_type' => 'farm_nfa_plan_locations',
      '#map_settings' => [
        'plan' => $this->getPlan(),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
