<?php

namespace Drupal\average_temp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides AverageAdminPage controller.
 */
class AverageAdminPage extends ControllerBase {

  /**
   * Database service definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * AverageAdminPage constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Builds page content.
   *
   * @return array
   *   Render array with content.
   */
  public function build() {
    $query = $this->database->select('average_temp', 'at');
    $query->fields('at', ['city', 'temp']);
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(created), '%d/%m/%Y')", 'Date');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(created), '%H:%i')", 'Time');

    return [
      '#type' => 'table',
      '#header' => ['city', 'temp', 'date', 'time'],
      '#rows' => $query->execute()->fetchAll(\PDO::FETCH_ASSOC),
    ];
  }

}
