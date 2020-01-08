<?php

namespace Drupal\atwork_group\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\Group;

/**
 * Class GroupForumConverter
 */
class GroupForumConverter extends EntityConverter  implements ParamConverterInterface {

  /**
   * The group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupForumConverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface group
   *   The group.
   */
  public function __construct(EntityTypeManagerInterface $group) {
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {

    $group = \Drupal::service('paramconverter.group.forum');
    if (NULL === $group) {
      return NULL;
    }

    // if numeric simply load group, else we need to convert group clean-name to group id
    if (is_numeric($value)) {
      $group = Group::load($value);
    }
    else {
      // user clean-name to find group in url_alias and use to determine group id, then load group
      $dst = "/groups/".$value;

      $database = \Drupal::database();
      $query = $database->select('path_alias', 'u');
      $query->condition('u.alias', $dst, '=');
      $query->fields('u', ['path']);
      $result = $query->execute()->fetchField();

      $group_id = str_replace('/group/', '', $result);

      $group = Group::load($group_id);
    }

    if (NULL === $group) {
      return NULL;
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // should only apply if we be looking at forums
    if (!empty($definition['type']) && $definition['type'] === 'entity:group') {
      return TRUE;
    }
    return FALSE;
  }
}
