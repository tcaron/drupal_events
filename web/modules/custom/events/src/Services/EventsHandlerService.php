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
    $endDate = $this->getFormattedDateTimeNow();
    $firstQuery = $this->getRelatedEventsMainQuery($endDate);
    $firstQuery->range(0,3);
    $firstQuery->condition('field_event_type',$type);
    $firstQuery->condition('nid',$nid, "<>");
    $result = $firstQuery->execute();
    if(count($result) == 3){
      return $result;
    }
    $prevResult = $result;
    //exclude previous results and current node for sub query
    $nids = array_values($prevResult);
    $nids[] = $nid;
    //call second query without field_event_type_condition
    if ($result = $this->getRelatedEventsSecondQuery($endDate,$result,$nids)){
      return $result;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedEventsSecondQuery(string $endDate, array $previousResult, array $nids): ?array {
    $query = $this->getRelatedEventsMainQuery($endDate);
    $query->range(0, 3 - count($previousResult));
    $query->condition('nid',$nids, "NOT IN");
    $result = $query->execute();
    if (!empty($result)) {
      return array_merge($previousResult, $result);
    }
    return NULL;
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
      //a second sorting may be necessary in case of second query running.
      ->sort('field_date_range.value');
  }

  /**
   * {@inheritdoc}
   */
  public function getEventstoUnpublish(): ?array {
    $endDate = $this->getFormattedDateTimeNow();
    $query = \Drupal::entityQuery('node')
      ->condition('type','event')
      ->accessCheck(FALSE)
      ->condition('status',TRUE)
      ->condition('field_date_range.end_value',$endDate,'<=');
    $result = $query->execute();
    if (!empty($result)) {
      return $result;
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormattedDateTimeNow(): string {
    $endDate = new DrupalDateTime('now');
    return $endDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }
}
