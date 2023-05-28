<?php

namespace Drupal\events\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\events\Services\EventsHandlerService;

/**
 * Provides a related events block.
 *
 * @Block(
 *   id = "events_related_block",
 *   admin_label = @Translation("Related Events"),
 *   category = @Translation("Custom")
 * )
 */
class RelatedEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The events.events_handler_service service.
   *
   * @var \\Drupal\events\Services\EventsHandlerService
   */
  protected $eventsHandlerService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RelatedEventsBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\events\Services\EventsHandlerService $events_handler_service
   *   The events.events_handler_service service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventsHandlerService $events_handler_service, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventsHandlerService = $events_handler_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('events.events_handler_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $nid = $node->id();
      $type = $node->get('field_event_type')->getString();
      if ($events = $this->getRelatedEventsToDisplay($nid,$type)) {
        $build = [
          '#theme' => 'related_events_block',
          '#title' => $this->t('Related events'),
          '#content' => $this->renderEntities($events)
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

  /**
   * Get the nids from service and load the nodes
   * @param $nid
   * @param $type
   * @return array|null
   */
  protected function getRelatedEventsToDisplay($nid,$type): ?array{
    if ($events = $this->eventsHandlerService->getRelatedEventsQuery($nid,$type)){
      try{
        $storage = $this->entityTypeManager->getStorage('node');
        if ($storage) {
          $nodes = $storage->loadMultiple($events);
          //add manual sorting in case of main query give less than 3 records
          usort($nodes,function($a,$b){
            //sorting on the begin date
            return strtotime($a->get('field_date_range')->getValue()[0]['value']) - strtotime($b->get('field_date_range')->getValue()[0]['value']);
          });
          return $nodes;
        }
      } catch (InvalidPluginDefinitionException $e) {
        return NULL;
      } catch (PluginNotFoundException $e) {
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * render entity
   * @param EntityInterface $entity
   * @param string $viewMode
   * @return array
   */
  public function renderEntity(EntityInterface $entity, string $viewMode = "related_block"): array {
    return $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity,$viewMode);
  }

  /**
   * render entities
   * @param array $entities
   * @param string $viewMode
   * @return array|null
   */
  public function renderEntities(array $entities,string $viewMode = "related_block"): ?array {
    $rendered = [];
    foreach ($entities as $entity) {
      if ($entity instanceof EntityInterface){
        $rendered[] = $this->renderEntity($entity,$viewMode);
      }
    }
    return $rendered ?? NULL;
  }
}
