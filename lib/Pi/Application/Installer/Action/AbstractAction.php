<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

namespace Pi\Application\Installer\Action;

use Pi;
use Pi\Application\Installer\Module;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;

/**
 * Module installer action abstract class
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
abstract class AbstractAction
{
    /** @var EventManager Installer event manager */
    protected $events;

    /** @var Event Installer event */
    protected $event;

    /** @var array Module config */
    protected $config;

    /** @var string Module identifier */
    protected $module;

    /** @var string Module directory */
    protected $directory;

    /** @var string Title for module */
    protected $title;

    /**
     * Constructor
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->setEvent($event);
    }

    /**
     * Action process
     *
     * Generates result as message array
     *
     * <code>
     *  array(
     *      'status'    => <true|false>,
     *      'message'   => <Message array>[],
     *  );
     * </code>
     *
     * @return void
     */
    abstract public function process();

    /**
     * Rollback upons failure
     *
     * @return bool
     */
    public function rollback()
    {
        return true;
    }

    /**
     * Setup EventManager
     *
     * @param EventManager $events
     * @return $this
     */
    public function setEvents(EventManager $events)
    {
        $this->events = $events;
        $this->attachDefaultListeners();

        return $this;
    }

    /**
     * Setup Event
     *
     * @param Event $event
     * @return $this
     */
    public function setEvent(Event $event)
    {
        $this->event        = $event;
        $this->config       = $event->getParam('config');
        $this->module       = $event->getParam('module');
        $this->directory    = $event->getParam('directory');
        $this->title        = $event->getParam('title')
                                ?: $this->config['meta']['title'];
        return $this;
    }

    /**
     * Setup result
     *
     * Set result generated by the action
     *
     * <code>
     *  array(
     *      'status'    => <true|false>,
     *      'message'   => <Message array>[],
     *  );
     * </code>
     *
     * @param string    $name   Operation name
     * @param array     $data   Result or message of the operation
     * @return $this
     */
    public function setResult($name, $data)
    {
        if (!isset($data['message'])) {
            $data['message'] = array();
        } else {
            $data['message'] = (array) $data['message'];
        }

        $result = $this->event->getParam('result');
        $result[$name] = $data;
        $this->event->setParam('result', $result);

        return $this;
    }

    /**
     * Attach default listeners
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
    }

    /**
     * Check if any module is dependent on the module
     *
     * @param Event $e
     * @return bool
     */
    public function checkDependent(Event $e)
    {
        $count = Pi::model('module_dependency')->count(array(
            'independent' => $e->getParam('module')
        ));
        if ($count > 0) {
            $this->setResult('dependent', array(
                'status'    => false,
                'message'   => 'The module has dependants on it.'
            ));
            return false;
        }

        return true;
    }

    /**
     * Check if the module is dependent on any module
     *
     * @param Event $e
     * @return bool
     */
    public function checkIndependent(Event $e)
    {
        $config = $this->event->getParam('config');
        if (empty($config['dependency'])) {
            return true;
        }
        $independents = $config['dependency'];
        $modules = Pi::registry('modulelist')->read();
        $missing = array();
        foreach ($independents as $independent) {
            if (!isset($modules[$independent])) {
                $missing[] = $independent;
            }
        }
        if ($missing) {
            $this->setResult('Independent', array(
                'status'    => false,
                'message'   => 'Modules required by this module: '
                               . implode(', ', $missing)
            ));
            return false;
        }

        return true;
    }

    /**
     * Store module dependency in DB
     *
     * @param Event $e
     * @return bool
     */
    public function createDependency(Event $e)
    {
        $config = $this->event->getParam('config');
        if (empty($config['dependency'])) {
            return true;
        }
        $module = $e->getParam('module');
        $model = Pi::model('module_dependency');
        foreach ($config['dependency'] as $independent) {
            $row = $model->createRow(array(
                'dependent'     => $module,
                'independent'   => $independent
            ));
            if (!$row->save()) {
                $model->delete(array('dependent' => $module));
                $this->setResult('dependency', array(
                    'status'    => false,
                    'message'   => 'Module dependency is not built.'
                ));
                return false;
            }
        }

        return true;
    }

    /**
     * Remove module dependency from DB
     *
     * @param Event $e
     * @return bool
     */
    public function removeDependency(Event $e)
    {
        //$config = $this->event->getParam('config');
        $model = Pi::model('module_dependency');
        $ret = $model->delete(array('dependent' => $e->getParam('module')));
        /*
        if ($ret < count($config['dependency'])) {
            $result = $e->getParam('result');
            $result['dependency'] = array(
                'status'    => false,
                'message'   => 'Module dependency is not removed completely.'
            );
            $e->setParam('result', $result);
            return false;
        }
        */

        return true;
    }

}
