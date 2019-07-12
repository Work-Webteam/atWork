<?php
  namespace Drupal\search_api_stats_block\Plugin\Block;

  use Drupal\Core\Block\BlockBase;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\search_api\Entity\Index;

  /**
   * Provides a 'SearchApiStatsBlock' block.
   *
   * @Block(
   *   id = "search_api_stats_block",
   *   admin_label = @Translation("Search API stats block"),
   *   deriver = "Drupal\search_api_stats_block\Plugin\Derivative\SearchApiStatsBlock"
   * )
   */
  class SearchApiStatsBlock extends BlockBase  {

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {

      $numPhrases = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30);
      $form = parent::blockForm($form, $form_state);
      $config = $this->getConfiguration();

      // Number of top search phrases to display
      $form['num_phrases'] = array(
        '#type' => 'select',
        '#title' => t('Number of top search phrases to display'),
        '#default_value' => empty($config['num_phrases']) ? 8 : $config['num_phrases'],
        '#options' => array_combine($numPhrases, $numPhrases),
      );

      // Path of search page
      $form['path'] = array(
        '#type' => 'textfield',
        '#title' => t('Path of search page'),
        '#default_value' => empty($config['path']) ? 'search' : $config['path'],
      );

      // Parameter name for the search phrase
      $form['param_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Parameter name for the search phrase'),
        '#default_value' => empty($config['param_name']) ? 'search' : $config['param_name'],
      );

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {

      $this->setConfigurationValue('num_phrases', $form_state->getValue('num_phrases'));
      $this->setConfigurationValue('path', $form_state->getValue('path'));
      $this->setConfigurationValue('param_name', $form_state->getValue('param_name'));
    }

    /**
     * @return array
     */
    public function build() {

      $config = $this->getConfiguration();
      $stats = $this->getStats();

      return array(
        '#theme' => 'search_api_stats_block',
        '#path' => $config['path'],
        '#param_name' => $config['param_name'],
        '#stats' => $stats,
        '#cache' => array(
          'max-age' => 0
        ),
      );
    }

    /**
     * @return array
     */
    protected function getStats() {

      $result = array();
      $database = \Drupal\Core\Database\Database::getConnection();

      $config = $this->getConfiguration();
      $serverName = $this->getServer();
      $indexName = $this->getDerivativeId();

      $stats = $database->queryRange("SELECT keywords, COUNT(*) as num FROM search_api_stats WHERE s_name = :s_name AND i_name=:i_name AND keywords != '' GROUP BY `keywords` ORDER BY num DESC", 0, $config['num_phrases'], array(':s_name' => $serverName, ':i_name' => $indexName));
      foreach ($stats as $stat) {
        $result[$stat->keywords] = $stat->num;
      }

      return $result;
    }

    /**
     * @return string
     */
    protected function getServer() {

      $result = '';

      $index = $this->getIndex();
      if (!empty($index)) {
        $result = $index->get('server');
      }

      return $result;
    }

    /**
     * @return Index
     */
    protected function getIndex() {

      $id = $this->getDerivativeId();
      $result = Index::load($id);;

      return $result;
    }
  }