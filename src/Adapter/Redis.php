<?php
namespace LightQueueClient\Adapter;

use LightQueueClient\Exception;

/**
 * Redis 队列适配器
 *
 * @author lindowx
 *        
 */
class Redis implements AdapterIntrface
{

    /**
     *
     * @var \Redis
     */
    protected $conn;

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::connect()
     */
    public function connect(array $config)
    {
        $config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => null
        ], $config);
        
        $redisId = "{$config['host']}:{$config['port']}";
        
        $this->conn = new \Redis();
        if (! $this->conn->connect($config['host'], $config['port'])) {
            throw new Exception("无法连接到队列Redis({$redisId})");
        }
        
        if (! empty($config['auth'])) {
            if (! $this->conn->auth($config['auth'])) {
                throw new Exception("队列Redis({$redisId}) 身份认证失败");
            }
        }
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::close()
     */
    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn == null;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::enqueue()
     */
    public function enqueue($name, $member, $toTail = false)
    {
        return $toTail ? $this->conn->rPush($name, $member) : $this->conn->lPush($name, $member);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::getStats()
     */
    public function getStats($name, $key = null)
    {
        if (empty($key)) {
            return $this->conn->hGetAll($name . '.stats');
        }
        
        return $this->conn->hGet($name . '.stats', $key);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::length()
     */
    public function length($name)
    {
        return $this->conn->lLen($name);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::setStats()
     */
    public function setStats($name, $key, $value)
    {
        $this->conn->hSet($name . '.stats', $key, $value);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::dequeue()
     */
    public function dequeue($name)
    {
        return $this->conn->rPop($name);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::view()
     */
    public function view($name, $offset, $length)
    {
        $stop = $offset + $length;
        return $this->conn->lrange($name, $offset, $stop);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::increaseStats()
     */
    public function increaseStats($name, $key)
    {
        return $this->conn->hIncrBy($name . '.stats', $key, 1);
    }

    /*
     * (non-PHPdoc)
     * @see \LogQueue\Adapter\AdapterIntrface::decreaseState()
     */
    public function decreaseState($name, $key)
    {
        return $this->conn->hIncrBy($name . '.stats', $key, - 1);
    }

    public function isConnected()
    {
        try {
            $info = $this->conn->info();
        } catch (\Exception $e) {}
        
        return ! empty($info);
    }
}
