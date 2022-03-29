<?php

namespace Drupal\average_temp;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service to getting new data from API.
 */
class TempService {

  /**
   * Base uri of average_temp api.
   */
  public const API_BASE_URI = 'http://api.openweathermap.org/data/2.5/weather';

  /**
   * Day to seconds.
   */
  public const DAY_SECONDS = 86400;

  /**
   * The database connection to be used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * ConfigFactory service definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal Time service definition.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The EntityTypeManager service definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a database object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle HTTP client.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(
    ClientInterface $http_client,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    TimeInterface $time,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->httpClient = $http_client;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->time = $time;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Return the data from the API in xml format.
   *
   * @param string $city
   *   The city name.
   *
   * @return false|string
   *   False on exception, response content if all ok.
   */
  public function getWeatherInformation(string $city) {
    $query = [];
    $config = $this->configFactory->get('average_temp.settings');
    $query['appid'] = Html::escape($config->get('key'));
    $query['q'] = Html::escape($city);
    $query['units'] = 'metric';

    try {
      $response = $this->httpClient->request('GET', self::API_BASE_URI, ['query' => $query]);
    }
    catch (GuzzleException $e) {
      watchdog_exception('average_temp', $e);
      return FALSE;
    }

    return $response->getBody()->getContents();
  }

  /**
   * Helper function to get Average temp by City name.
   *
   * @param string $city
   *   City name.
   * @param int $days
   *   Count of days.
   *
   * @return int
   *   Average temp value.
   */
  public function getAverageTempByCity(string $city, int $days) {
    $average_temp = 0;
    $range = self::DAY_SECONDS * $days;
    $current_timestamp = $this->time->getCurrentTime();
    $start_date = $current_timestamp - $range;

    $query = $this->database->select('average_temp', 'at');
    $query->addExpression('SUM(temp)', 'temp');
    $query->addExpression('COUNT(temp)', 'count');
    $query->condition('city', $city);
    $query->condition('created', $start_date, '>=');
    if ($result = array_filter($query->execute()->fetchAssoc())) {
      $average_temp = (int) round($result['temp'] / $result['count']);
    }

    return $average_temp;
  }

  /**
   * Insert new temp data by cities.
   */
  public function setCurrentTempData() {
    // Load only blocks that use needed plugin to get City settings.
    $blocks = $this->entityTypeManager->getStorage('block')
      ->loadByProperties(['plugin' => 'average_temp_block']);
    $cities = [];
    foreach ($blocks as $block) {
      $cities[] = $block->get('settings')['city'] ?? NULL;
    }

    $current_timestamp = $this->time->getRequestTime();
    foreach (array_filter($cities) as $city) {
      // Check all needed data to be present before insert.
      if (($json = $this->getWeatherInformation($city))
        && ($encode = json_decode($json, 1))
        && ($city = $encode['name'] ?? NULL)
        && ($temp = $encode['main']['temp'] ?? NULL)
      ) {
        $this->database
          ->insert('average_temp')
          ->fields([
            'created' => $current_timestamp,
            'city' => $city,
            'temp' => $temp,
            'data' => $json,
          ])
          ->execute();
      }
    }
  }

  /**
   * Helper function to get grouped data by Cities.
   *
   * @return array
   *   Array with stored data.
   */
  public function getStoredCitiesWithDates() {
    $query = $this->database->select('average_temp', 'at');
    $query->addField('at', 'city');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(min(created)), '%d/%m/%Y %H:%i')", 'min');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(max(created)), '%d/%m/%Y %H:%i')", 'max');
    $query->groupBy('city');
    $cities = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $options = [];
    foreach ($cities as $data) {
      $options[$data['city']] = $data['city'] . ': ' . $data['min'] . ' - ' . $data['max'];
    }

    return $options;
  }

}
