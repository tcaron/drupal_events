<?php

namespace Drupal\events\Services;

use Drupal\Core\Entity\Query\QueryInterface;

interface EventsHandlerServiceInterface
{
  /**
   * Get related events query
   * @param string $nid
   * @param string $type
   * @return array|null
   */
  public function getRelatedEventsQuery(string $nid, string $type): ?array;

  /**
   * Get the main query to retrieve related events
   * @param string $endDate
   * @return QueryInterface
   */
  public function getRelatedEventsMainQuery(string $endDate): QueryInterface;

  /**
   * Get the second query, based on the main query when the main return less than 3 results
   * @param string $endDate
   * @param array $previousResult
   * @param array $nids
   * @return array|null
   */
  public function getRelatedEventsSecondQuery(string $endDate, array $previousResult, array $nids): ?array;

  /**
   * get events to unpublish
   * @return array|null
   */
  public function getEventstoUnpublish(): ?array;

  /**
   * return the now datetime well formatted for entity query
   * @return string
   */
  public function getFormattedDateTimeNow(): string;
}
