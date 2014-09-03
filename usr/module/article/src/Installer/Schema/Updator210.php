<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Article\Installer\Schema;

use Pi;
use Pi\Application\Installer\Schema\AbstractUpdator;

/**
 * Module schema update handler
 *
 * @author Zongshu <lin40553024@163.com>
 */
class Updator210 extends AbstractUpdator
{
    /**
     * Update article table schema
     *
     * @param string $version
     *
     * @return bool
     */
    public function upgrade($version)
    {
        $result = $this->from111($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from111($version)
    {
        $result = true;
        if (version_compare($version, '1.2.1', '<')) {
            $module = $this->handler->getParam('module');
            
            // Create table cluster
            $table  = Pi::model('cluster', $module)->getTable();
            $sql =<<<EOD
CREATE TABLE `{$table}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `left`            int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `right`           int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `depth`           int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `name`            varchar(64)           NOT NULL DEFAULT '',
  `slug`            varchar(64)           DEFAULT NULL,
  `title`           varchar(64)           NOT NULL DEFAULT '',
  `description`     varchar(255)          NOT NULL DEFAULT '',
  `image`           varchar(255)          NOT NULL DEFAULT '',

  PRIMARY KEY           (`id`),
  UNIQUE KEY `name`     (`name`),
  UNIQUE KEY `slug`     (`slug`)
);
EOD;
            $result = $this->querySchema($sql, $module);
            if (false === $result) {
                return $result;
            }
            
            // Add field to article table
            $tableArticle = Pi::db()->prefix('article', $module);
            $sql =<<<EOD
ALTER TABLE {$tableArticle} ADD COLUMN `cluster` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `category`;
ALTER TABLE {$tableArticle} ADD INDEX `cluster` (cluster);
EOD;
            $result = $this->queryTable($sql);
            if (false === $result) {
                return $result;
            }
            
            // Add field to draft table
            $tableArticle = Pi::db()->prefix('draft', $module);
            $sql =<<<EOD
ALTER TABLE {$tableArticle} ADD COLUMN `cluster` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `category`;
EOD;
            $result = $this->queryTable($sql);
            if (false === $result) {
                return $result;
            }
        }

        return $result;
    }
}