<?php

/**
 * @file
 * DbHandler class.
 */

namespace Drupal\db_maintenance\Module\Db;

//use Drupal\db_maintenance\Module\Config\ConfigHandler;
use Drupal\Core\Database\Database;
use Drupal\db_maintenance\Module\Common\WatchdogAdapter;
use Drupal\db_maintenance\Module\Config\ConfigHandler;
use Drupal\db_maintenance\Module\Db\DbServer\DbServerHandlerFactory;
use Psr\Log\LogLevel;

/**
 * DbHandler class.
 */
class DbHandler {


  /**
   * Performs the maintenance.
   */
  public static function optimizeTables() {
    $dbs = self::getDatabases();

    foreach ($dbs as $db => $connection) {
      $db_name = $connection['default']['database'];

      $all_tables = ConfigHandler::getProcessAllTables();
      if ($all_tables) {
        $config_tables = self::listTables($db);
      }
      else {
        $config_tables = ConfigHandler::getTableList($db_name);
      }

      // Only proceed if tables are selected for this database.
      if (is_array($config_tables) && count($config_tables) > 0) {

        foreach($config_tables as $key => $table_name) {
          // Set the database to query.
          $previous = Database::setActiveConnection($db);

          $table_clear = PrefixHandler::clearPrefix($table_name);

          if (Database::getConnection() ->schema() ->tableExists($table_clear)) {
            $handler = DbServerHandlerFactory::getDbServerHandler();
            $handler->optimizeTable($table_name);
          }
          else {
            WatchdogAdapter::watchdog('db_maintenance',
              '@table table in @db database was configured to be optimized but does not exist.',
              array('@db' => $db_name, '@table' => $table_name), LogLevel::NOTICE);
          }

          // Return to the previously set database.
          Database::setActiveConnection($previous);
          WatchdogAdapter::watchdog('db_maintenance',
            'Optimized @table table in @db database.',
            array('@db' => $db_name, '@table' => $table_name), LogLevel::DEBUG);
        }

        if (ConfigHandler::getWriteLog()) {
          $tables = implode(', ', $config_tables);
          WatchdogAdapter::watchdog('db_maintenance',
            'Optimized tables in @db database: @tables',
            array('@db' => $db_name, '@tables' => $tables), LogLevel::INFO);
        }
      }
    }

    ConfigHandler::setCronLastRun(\Drupal::time()->getRequestTime());
  }

  /**
   * Gets a list of all the tables in a database.
   *
   * @param string $db
   *   The name of the database connection to query for tables.
   *
   * @return array
   *   Array representing the tables in the specified database.
   */
  public static function listTables($db) {
    $table_names = array();

    // Set the database to query.
    $previous = Database::setActiveConnection($db);

    $handler = DbServerHandlerFactory::getDbServerHandler();
    $result = $handler->listTables();

    // Return to the previously set database.
    Database::setActiveConnection($previous);
    foreach ($result as $table_name) {
      $table_name = current($table_name);
      $table_names[$table_name] = $table_name;
    }
    return $table_names;
  }

  /**
   * Gets the list of all databases.
   *
   * Use result of this function instead of global $databases.
   */
  public static function getDatabases() {
    $dbs = Database::getAllConnectionInfo();
    return $dbs;
  }

}
