<?php

namespace olessavluk\settings;

use Yii;
use yii\db\Query;
use yii\db\Connection;
use yii\caching\Cache;
use yii\base\Component;
use yii\web\Application;

class SettingsComponent extends Component
{
    public $tableName = '{{%settings}}';
    public $cacheTime = 0; //never expire
    public $defaults = [];

    /** @var Connection */
    protected $db;
    public $dbName = 'db';

    /** @var  Cache */
    protected $cache;
    public $cacheName = 'cache';

    protected $items = [];
    protected $toSave = [];
    protected $toRemove = [];

    public function init()
    {
        parent::init();

        $this->db = $this->db ? $this->db : Yii::$app->get($this->dbName);
        $this->cache = $this->cache ? $this->cache : Yii::$app->get($this->cacheName);;

        $this->load();
        $this->fillDefaults();

        Yii::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'commit']);
    }

    /**
     * Get settings entry
     *
     * @param $category
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($category, $key, $default = null)
    {
        return isset($this->items[$category][$key]) ? $this->items[$category][$key] : $default;
    }

    /**
     * Set settings entry
     *
     * @param string $category
     * @param string $key
     * @param string $value
     * @param bool|true $permanent
     */
    public function set($category, $key, $value, $permanent = true)
    {
        $this->items[$category][$key] = $value;

        if ($permanent) {
            $this->toSave[$category][$key] = $value;
            unset($this->toRemove[$category][$key]);
        }
    }

    /**
     * Delete settings entry
     *
     * @param $category
     * @param $key
     * @param bool|true $permanent
     */
    public function delete($category, $key, $permanent = true)
    {
        unset($this->items[$category[$key]]);

        if ($permanent) {
            $this->toRemove[$category][$key] = true;
            unset($this->toSave[$category][$key]);
        }
    }

    protected function load()
    {
        $this->items = $this->cache->get('settings');
        if (!$this->items) {
            $this->items = [];
            $rows = (new Query())
                ->from($this->tableName)
                ->all($this->db);
            foreach ($rows as $row) {
                $this->items[$row['category']][$row['key']] = @unserialize($row['value']);
            }

            $this->cache->set('settings', $this->items, $this->cacheTime);
        }
    }

    protected function fillDefaults() {
        foreach ($this->defaults as $category => $keyvalue) {
            foreach ($keyvalue as $key => $value) {
                if (!isset($this->items[$category][$key])) {
                    $this->items[$category][$key] = $value;
                }
            }
        }
    }

    protected function commit() {
        /* @fixme: implement batch operations */
        foreach ($this->toRemove as $category => $keyvalue) {
            foreach ($keyvalue as $key => $value) {
                $this
                    ->db
                    ->createCommand()
                    ->delete($this->tableName, [
                        'category' => $category,
                        'key' => $key
                    ])->execute();
            }
        }

        foreach ($this->toSave as $category => $keyvalue) {
            foreach ($keyvalue as $key => $value) {
                $obj = (new Query())
                    ->from($this->tableName)
                    ->where(['category' => $category, 'key' => $key])
                    ->one($this->db);
                $command = $this->db->createCommand();
                if ($obj) {
                    $command = $command->update($this->tableName,
                        ['value' => @serialize($value)],
                        ['category' => $category, 'key' => $key]
                    );
                } else {
                    $command = $command->insert($this->tableName, [
                        'category' => $category,
                        'key' => $key,
                        'value' => @serialize($value)
                    ]);
                }
                $command->execute();
            }
        }

        if (count($this->toRemove) + count($this->toSave) > 0) {
            $this->cache->delete('settings');
        }

        $this->toRemove = [];
        $this->toSave = [];
    }
}
