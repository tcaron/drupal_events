<?php

namespace Drupal\events\Services;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class EventsHandlerService implements EventsHandlerServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getRelatedEventsQuery(string $nid, string $type): ?array{
    $result = NULL;
    $endDate = new DrupalDateTime('now');
    $endDate = $endDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $firstQuery = $this->getRelatedEventsMainQuery($endDate);
    $firstQuery->range(0,3);
    $firstQuery->condition('field_event_type',$type);
    $firstQuery->condition('nid',$nid, "<>");
    // do not make the sort by date asc here in case of count result inferior to 3
    $result = $firstQuery->execute();
    if(count($result) == 3){
      return $result;
    }
    $prevResult = $result;
    //exclude previous results and current node for sub query
    $nids = array_values($prevResult);
    $nids[] = $nid;
    $query = $this->getRelatedEventsMainQuery($endDate);
    $query->range(0, 3-count($prevResult));
    $query->condition('nid',$nids, "NOT IN");
    $result = $query->execute();
    if (!empty($result)) {
      $result = array_merge($prevResult, $result);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedEventsMainQuery(string $endDate): QueryInterface {
    return \Drupal::entityQuery('node')
      ->condition('type','event')
      ->accessCheck(FALSE)
      ->condition('status',TRUE)
      ->condition('field_date_range.end_value',$endDate,'>=')
      ->sort('field_date_range.value');
  }

  /**
   * {@inheritdoc}
   */
  public function getEventstoUnpublish(): ?array {
    $result = NULL;
    $endDate = new DrupalDateTime('now');
    $endDate = $endDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = \Drupal::entityQuery('node')
      ->condition('type','event')
      ->accessCheck(FALSE)
      ->condition('status',TRUE)
      ->condition('field_date_range.end_value',$endDate,'<=');
    $query = $query->execute();
    if (!empty($query)) {
      $result = $query;
    }
    return $result;
  }
}
