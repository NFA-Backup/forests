<?php

namespace Drupal\farm_nfa;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\farm_map\Event\MapRenderEvent;
use Drupal\farm_map\LayerStyleLoaderInterface;
use Drupal\farm_ui_map\EventSubscriber\MapRenderEventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates MapRenderEventSubscriber to exclude some routes.
 */
class MapRenderEventSubscriberDecorator extends MapRenderEventSubscriber {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs a new MapRenderEventSubscriberDecorator.
   *
   *   The entity type manager service.
   *   The layer style loader service.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LayerStyleLoaderInterface $layer_style_loader, RouteMatchInterface $routeMatch, RequestStack $requestStack) {
    parent::__construct($entity_type_manager, $layer_style_loader);
    $this->routeMatch = $routeMatch;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('farm_map.layer_style_loader'),
      $container->get('current_route_match'),
      $container->get('request_stack')->getCurrentRequest());
  }

  /**
   * React to the MapRenderEvent.
   *
   * @param \Drupal\farm_map\Event\MapRenderEvent $event
   *   The MapRenderEvent.
   */
  public function onMapRender(MapRenderEvent $event) {
    parent::onMapRender($event);

    // The layer switcher is shown on all maps.
    $event->addBehavior('farm_nfa_layerswitcher_sidebar');

    $farm_nfa_routes = [
      'farm_nfa.plan.add_task',
      'entity.asset.gfw_tab',
      'entity.plan.canonical',
      'entity.plan.gfw',
    ];
    $route_name = $this->routeMatch->getRouteName();
    if (in_array($route_name, $farm_nfa_routes)) {
      $settings[$event->getMapTargetId()]['asset_type_layers']['all_locations'] = [
        'label' => $this->t('All locations'),
        'filters' => [
          'is_location' => 1,
        ],
        'color' => 'grey',
        'zoom' => FALSE,
      ];
      $event->addSettings($settings);
      $event->addBehavior('asset_type_layers');
    }

    if ($route_name == 'entity.asset.canonical') {
      $asset = $this->routeMatch->getParameter('asset');
      if ($asset->bundle() == 'land' && $asset->hasField('land_type') && !$asset->get('land_type')->isEmpty()) {
        $event->addBehavior('farm_nfa_land_cfr_layer');
        // Add the GFW layer.
        $settings[$event->getMapTargetId()] = [
          'asset' => $this->routeMatch->getRawParameter('asset'),
          'host' => $this->request->getHost(),
          'asset_type' => $asset->bundle(),
          'land_type' => $asset->get('land_type')->value,
        ];
        $event->addSettings($settings);
        $event->addBehavior('farm_nfa_gfw_alerts');
      }
    }
  }

}
