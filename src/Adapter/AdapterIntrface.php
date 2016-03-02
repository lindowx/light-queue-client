<?php
namespace LightQueueClient\Adapter;

/**
 * 队列连接适配器接口
 * 
 * @author lindowx
 *
 */
interface AdapterIntrface
{
    /**
     * @param array $config
     */
    public function connect(array $config);
    
    public function close();
    
    /**
     * @param string $name
     * @return int
     */
    public function length($name);
    
    /**
     * @param string $name
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function view($name, $offset, $length);
    
    /**
     * @param string $name
     * @param string $key
     * @param string $value
     * @param boolean $toTail
     * @return boolean
     */
    public function enqueue($name, $member, $toTail = false);
    
    /**
     * @param string $name
     * @return string
     */
    public function dequeue($name);
    
    /**
     * @param string $name
     * @param string $key
     * @return string
     */
    public function getStats($name, $key = null);
    
    /**
     * @param string $name
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function setStats($name, $key, $value);
    
    /**
     * @param unknown $name
     * @param unknown $key
     */
    public function increaseStats($name, $key);
    
    /**
     * @param string $name
     * @param string $key
     */
    public function decreaseState($name, $key);
    
    /**
     * @return boolean
     */
    public function isConnected();
}
