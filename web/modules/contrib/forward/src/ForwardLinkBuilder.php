<?php

namespace Drupal\forward;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for building markup for a Forward link on an entity.
 */
class ForwardLinkBuilder implements ForwardLinkBuilderInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The link generation service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Constructs a ForwardLinkBuilder object.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   The link generation service.
   */
  public function __construct(Token $token_service, RendererInterface $renderer, LinkGenerator $link_generator) {
    $this->tokenService = $token_service;
    $this->renderer = $renderer;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('renderer'),
      $container->get('link_generator')
    );
  }

  /**
   * Build a link.
   */
  private function buildLink(EntityInterface $entity, array $settings) {
    $langcode = $entity->language()->getId();
    $token = ['forward' => ['entity' => $entity]];
    $title = $this->tokenService->replace($settings['forward_interface_title'], $token, ['langcode' => $langcode]);
    $title_text = $title;

    $html = FALSE;
    // Output the correct style of link.
    $default_icon = drupal_get_path('module', 'forward') . '/images/forward.gif';
    $custom_icon = $settings['forward_link_icon'];
    $link_style = $settings['forward_link_style'];
    switch ($link_style) {
      // Text only is a "noop" since the title text is already setup above.
      // Image only.
      case 1:
        $img = $custom_icon ? $custom_icon : $default_icon;
        $render_array = [
          '#theme' => 'image',
          '#uri' => $img,
          '#alt' => $title,
          '#attributes' => ['class' => ['forward-icon']],
        ];
        $title = $this->renderer->render($render_array);
        $html = TRUE;
        break;

      // Image and text.
      case 2:
        $img = $custom_icon ? $custom_icon : $default_icon;
        $render_array = [
          'image' => [
            '#theme' => 'image',
            '#uri' => $img,
            '#alt' => $title,
            '#attributes' => [
              'class' => [
                'forward-icon',
                'forward-icon-margin',
              ],
            ],
          ],
          'text' => ['#markup' => $title_text],
        ];
        $title = $this->renderer->render($render_array);
        $html = TRUE;
        break;
    }
    $attributes = [
      'title' => $this->tokenService->replace($settings['forward_link_title'], $token, ['langcode' => $langcode]),
      'class' => ['forward-page'],
    ];
    if ($settings['forward_link_nofollow']) {
      $attributes['rel'] = 'nofollow';
    }

    $entity_id = $entity->id();
    $entity_type = $entity->getEntityTypeId();
    $url = Url::fromUri("internal:/forward/{$entity_type}/{$entity_id}");
    $url->setOptions(
      [
        'html' => $html,
        'attributes' => $attributes,
      ]
    );
    return ['title' => $title, 'url' => $url, 'attributes' => $attributes];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForwardEntityLink(EntityInterface $entity, array $settings) {
    $link = $this->buildLink($entity, $settings);
    if ($settings['forward_link_inline']) {
      // Render the link inline with other node links.
      $render_array = [
        '#theme' => 'links',
        '#links' => [
          'forward' => [
            'url' => $link['url'],
            'title' => $link['title'],
            'attributes' => $link['attributes'],
          ],
        ],
        '#attributes' => [
          'class' => ['links', 'inline'],
        ],
        '#attached' => [
          'library' => [
            'forward/forward',
          ],
        ],
        '#weight' => $settings['forward_interface_weight'],
      ];
    }
    else {
      // Standard render.
      $render_array = [
        '#markup' => $this->linkGenerator->generate($link['title'], $link['url']),
        '#attached' => [
          'library' => [
            'forward/forward',
          ],
        ],
        '#weight' => $settings['forward_interface_weight'],
      ];
    }
    return $render_array;
  }

}
