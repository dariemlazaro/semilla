<?php

/**
 * @file
 * Contains \Drupal\db_maintenance\Form\DbMaintenanceAdminSettings.
 */

namespace Drupal\db_maintenance\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\db_maintenance\Module\Config\ConfigHandler;
use Drupal\db_maintenance\Module\Db\DbHandler;
use Drupal\db_maintenance\Module\Interval\IntervalHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

class DbMaintenanceAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'db_maintenance_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('db_maintenance.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['db_maintenance.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $dbs = DbHandler::getDatabases();

    // drupal_add_css(drupal_get_path('module', 'db_maintenance') . '/db_maintenance.css');

//    $form = [];
    $form['write_log'] = [
      '#type' => 'checkbox',
      '#title' => 'Log OPTIMIZE queries',
      //'#default_value' => \Drupal::config('db_maintenance.settings')->get('write_log'),
      '#default_value' => ConfigHandler::getWriteLog(),
      '#description' => t('If enabled, a watchdog entry will be made each time tables are optimized, containing information which tables were involved.'),
    ];

    $options = [
      0 => t('Run during every cron'),
      3600 => t('Hourly'),
      7200 => t('Bi-Hourly'),
      86400 => t('Daily'),
      172800 => t('Bi-Daily'),
      604800 => t('Weekly'),
      1209600 => t('Bi-Weekly'),
      2592000 => t('Monthly'),
      5184000 => t('Bi-Monthly'),
    ];

    $url = Url::fromRoute('db_maintenance.optimize_tables_page');
    $token = \Drupal::csrfToken()->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
    $internal_link = Link::fromTextAndUrl(t('Optimize now.'), $url) ->toString();

    $form['cron_frequency'] = [
      '#type' => 'select',
      '#title' => t('Optimize tables'),
      '#options' => $options,
      '#default_value' => ConfigHandler::getCronFrequency(),
      '#description' => t('Select how often database tables should be optimized.') . ' ' . $internal_link,
    ];
    // Set the databases array if not already set in $db_url.
    $options = [];

    // Visibility.
    $states1 = array(
      'visible' => array(
        ':input[name="use_time_interval"]' => array(
          'checked' => TRUE,
        ),
      ),
    );

    $form['use_time_interval'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Use time interval'),
      '#default_value' => ConfigHandler::getUseTimeInterval(),
      '#description'   => t('Start optimization only within predefined time interval.'),
    );

    $form['time_interval_start'] = array(
      '#type' => 'textfield',
      '#maxlength' => 25,
      '#title'         => t('Time interval start'),
      '#default_value' => ConfigHandler::getTimeIntervalStart(),
      '#description'   => t('Time interval start in 24 hour format H:i (HH:MM) like 23:30 or 01:00.'),
      '#states' => $states1,
    );

    $form['time_interval_end'] = array(
      '#type' => 'textfield',
      '#maxlength' => 25,
      '#title'         => t('Time interval end'),
      '#default_value' => ConfigHandler::getTimeIntervalEnd(),
      '#description'   => t('Time interval end in 24 hour format H:i (HH:MM) like 23:30 or 01:00.'),
      '#states' => $states1,
    );

    // Visibility.
    $states = [
      'visible' => [
        ':input[name="all_tables"]' => [
          'checked' => FALSE
          ]
        ]
      ];

    $form['all_tables'] = [
      '#type' => 'checkbox',
      '#title' => t('Optimize all tables'),
      '#default_value' => ConfigHandler::getProcessAllTables(),
      '#description' => t('Automatically optimize all tables in the database(s) without having to select them first.'),
    ];

    // Loop through each database and list the possible tables to optimize.
    foreach ($dbs as $db => $connection) {
      $options = DbHandler::listTables($db);

      $form['table_list_' . $connection['default']['database']] = [
        '#type' => 'select',
        '#title' => t('Tables in the @db database', [
          '@db' => $connection['default']['database'] == 'default' ? 'Drupal' : $connection['default']['database']
        ]),
        '#options' => $options,
        '#default_value' => ConfigHandler::getTableList($connection['default']['database'], ''),
        '#description' => t('Selected tables will be optimized during cron runs.'),
        '#multiple' => TRUE,
        '#attributes' => [
          'size' => 17
        ],
        '#states' => $states,
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check time interval.
    if ($form_state->getValue('use_time_interval') == TRUE) {
      // Check start value.
      $time = $form_state->getValue('time_interval_start');
      if (!IntervalHandler::checkTime($time)) {
        $form_state->setErrorByName('time_interval_start',
          $this->t('Invalid time format. Should be 24 hour format H:i (HH:MM) like 23:30 or 01:00.')
        );
      }
      // Check end value.
      $time = $form_state->getValue('time_interval_end');
      if (!IntervalHandler::checkTime($time)) {
        $form_state->setErrorByName('time_interval_end',
          $this->t('Invalid time format. Should be 24 hour format H:i (HH:MM) like 23:30 or 01:00.')
        );
      }
    }

  }

}
