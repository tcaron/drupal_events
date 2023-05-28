<?php

namespace Drupal\events\Plugin\QueueWorker;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\events\Services\EventsHandlerService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unpublish events content
 *
 * @QueueWorker(
 *   id = "events_unpublish_events_queue_worker",
 *   title = @Translation("Unpublish events"), cron = {"time" = 10}
 * )
 */
class UnpublishEventsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager){
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('entity_type.manager')
    );
  }
  public function processItem($data)
  {
    try{
      $storage = $this->entityTypeManager->getStorage('node');
      if ($storage) {
        $node = $storage->load($data['nid']);
        if ($node instanceof NodeInterface) {
          //set the status to 0
          $node->setUnpublished();
          $node->save();
        }
      }
    } catch (InvalidPluginDefinitionException $e) {
      return NULL;
    } catch (PluginNotFoundException $e) {
      return NULL;
    }
  }
}
