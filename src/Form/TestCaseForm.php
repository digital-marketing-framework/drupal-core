<?php

namespace Drupal\dmf_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dmf_core\Entity\TestCase;

/**
 * Form handler for Test Case add and edit forms.
 */
class TestCaseForm extends EntityForm
{
    /**
     * @param array<mixed> $form
     *
     * @return array<string,mixed>
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        /** @var TestCase $testCase */
        $testCase = $this->entity;

        // Add vertical tabs container.
        $form['tabs'] = [
            '#type' => 'vertical_tabs',
            '#weight' => 99,
        ];

        // General tab.
        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('General'),
            '#group' => 'tabs',
            '#weight' => 0,
            '#open' => true,
        ];

        $form['general']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#maxlength' => 255,
            '#default_value' => $testCase->getLabel(),
            '#description' => $this->t('The human-readable label of the test case.'),
            '#required' => true,
        ];

        $form['general']['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $testCase->id(),
            '#machine_name' => [
                'exists' => $this->exist(...),
                'source' => ['general', 'label'],
            ],
            '#disabled' => !$testCase->isNew(),
        ];

        $form['general']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#maxlength' => 255,
            '#default_value' => $testCase->getName(),
            '#description' => $this->t('The machine name of the test case.'),
            '#required' => true,
        ];

        $form['general']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#default_value' => $testCase->getDescription(),
            '#description' => $this->t('A description of the test case.'),
            '#rows' => 3,
        ];

        // Test tab.
        $form['test'] = [
            '#type' => 'details',
            '#title' => $this->t('Test'),
            '#group' => 'tabs',
            '#weight' => 1,
        ];

        $form['test']['type'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Type'),
            '#maxlength' => 255,
            '#default_value' => $testCase->getType(),
            '#description' => $this->t('The test processor type.'),
        ];

        $form['test']['hash'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Hash'),
            '#maxlength' => 255,
            '#default_value' => $testCase->getHash(),
            '#description' => $this->t('Hash for tracking changes.'),
        ];

        $form['test']['serialized_input'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Serialized Input'),
            '#default_value' => $testCase->get('serialized_input') ?? '',
            '#description' => $this->t('JSON-encoded input data.'),
            '#rows' => 10,
        ];

        $form['test']['serialized_expected_output'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Serialized Expected Output'),
            '#default_value' => $testCase->get('serialized_expected_output') ?? '',
            '#description' => $this->t('JSON-encoded expected output data.'),
            '#rows' => 10,
        ];

        return $form;
    }

    /**
     * @param array<mixed> $form
     *
     * @return array<string,mixed>
     */
    protected function actions(array $form, FormStateInterface $form_state): array
    {
        $actions = parent::actions($form, $form_state);

        $returnUrl = $this->getReturnUrl($form_state);

        if (isset($actions['delete'])) {
            $actions['delete']['#url']->setOption('query', ['destination' => $returnUrl]);
        }

        $actions['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => Url::fromUserInput($returnUrl),
            '#attributes' => [
                'class' => ['button'],
            ],
            '#weight' => 15,
        ];

        $actions['save_continue'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save and continue editing'),
            '#submit' => ['::submitForm', '::save', '::saveAndContinue'],
            '#weight' => 10,
        ];

        $actions['submit']['#weight'] = 5;

        return $actions;
    }

    /**
     * Get the return URL passed from controller.
     */
    protected function getReturnUrl(FormStateInterface $form_state): string
    {
        return $form_state->get('dmf_returnUrl') ?? '/admin/dmf';
    }

    /**
     * Get the edit URL passed from controller.
     */
    protected function getEditUrl(FormStateInterface $form_state): string
    {
        return $form_state->get('dmf_editUrl') ?? '';
    }

    /**
     * Form submission handler for "Save and continue editing".
     *
     * @param array<mixed> $form
     */
    public function saveAndContinue(array $form, FormStateInterface $form_state): void
    {
        $editUrl = $this->getEditUrl($form_state);
        if ($editUrl !== '') {
            $form_state->setRedirectUrl(Url::fromUserInput($editUrl));
        }
    }

    /**
     * @param array<mixed> $form
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var TestCase $testCase */
        $testCase = $this->entity;

        $status = $testCase->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Created the %label test case.', [
                '%label' => $testCase->getLabel(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Saved the %label test case.', [
                '%label' => $testCase->getLabel(),
            ]));
        }

        $returnUrl = $this->getReturnUrl($form_state);
        $form_state->setRedirectUrl(Url::fromUserInput($returnUrl));

        return $status;
    }

    /**
     * Helper function to check whether a test case configuration entity exists.
     */
    public function exist(string $id): bool
    {
        $entity = $this->entityTypeManager
          ->getStorage('dmf_test_case')
          ->getQuery()
          ->condition('id', $id)
          ->accessCheck(false)
          ->execute();

        return (bool)$entity;
    }
}
