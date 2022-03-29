<?php

namespace Drupal\average_temp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\average_temp\TempService;

/**
 * Provides a block with Average temp display.
 *
 * @Block(
 *   id = "average_temp_block",
 *   admin_label = @Translation("Average Temp block"),
 * )
 */
class AverageTempBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\average_temp\TempService
   */
  protected $tempService;

  /**
   * The database connection to be used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a AverageTempBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\average_temp\TempService $temp_service
   *   The information from the Temp service for this block.
   * @param \Drupal\Core\Database\Connection $database
   *   The Connection object containing the key-value tables.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TempService $temp_service, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempService = $temp_service;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('average_temp.service'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the name of City'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the name of City'),
      '#default_value' => $config['city'] ?? '',
    ];

    $form['count_days'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Number of days'),
      '#default_value' => $config['count_days'] ?? 1,
      '#required' => TRUE,
      '#description' => $this->t('Enter number of days for the average temperature.'),
    ];

    if ($options = $this->tempService->getStoredCitiesWithDates()) {
      $form['delete'] = [
        '#type' => 'details',
        '#title' => $this->t('Show options for delete as outdated'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      $form['delete']['outdated'] = [
        '#title' => $this->t('Check outdated'),
        '#type' => 'checkboxes',
        '#description' => $this->t('Check please cities for delete records'),
        '#options' => $options,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $city_name = $form_state->getValue('city');
    $this->setConfigurationValue('city', $city_name);
    $this->setConfigurationValue('count_days', $form_state->getValue('count_days'));

    // Creating first value.
    $this->tempService->getWeatherInformation($city_name);

    if ($outdated = $form_state->getValue('delete')['outdated'] ?? NULL) {
      foreach (array_filter($outdated) as $city) {
        $query = $this->database->delete('average_temp');
        $query->condition('city', $city);
        $query->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $city = $config['city'];
    $days = $config['count_days'];
    $average_temp = $this->tempService->getAverageTempByCity($city, (int) $days);
    return [
      '#type' => 'markup',
      '#markup' => $average_temp
        ? $this->t('Average temp in @city: @temp', [
          '@city' => $city,
          '@temp' => $average_temp,
        ])
        : '',
      '#cache' => [
        'max-age' => $average_temp ? 3600 : 0,
      ],
    ];
  }

}
