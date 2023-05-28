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
   * get events to unpublish
   * @return array|null
   */
  public function getEventstoUnpublish(): ?array;
}
