<?php
namespace LightQueueClient;

use LightQueueClient\Adapter\AdapterIntrface;
use LightQueueClient\Message;
/**
 * 队列
 *
 * @author lindowx
 *
 */
class Queue
{
    /**
     * 队列名称
     *
     * @var string
     */
    protected $name;

    /**
     * 队列连接
     *
     * @var AdapterIntrface
     */
    protected $adapter;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config;

    /**
     * @var \LightQueueClient\Queue
     */
    protected $recycleBin;

    /**
     * 队列名称前缀
     *
     * @var string
     */
    protected $queueNamePrefix;

    /**
     * 序列化消息
     *
     * @param Message $msg  原始消息对象
     * @return string
     */
    public static function messageSerialize(Message $msg)
    {
        return msgpack_pack($msg->toArray());
    }

    /**
     * 反序列化消息
     *
     * @param string $encodedMsg    序列化的消息内容
     * @return Message | null
     */
    public static function messageUnserialize($serializedMsg)
    {
        $msgArr = msgpack_unpack($serializedMsg);
        if ($msgArr['__lqcmc'] == Message::class) {
            $msg = new Message();
            unset($msgArr['__lqcmc']);

            $msg->initByArray($msgArr);
            return $msg;
        }
    }

    /**
     * Constructor
     *
     * @param array $config  队列配置
     * @throws Zend_Exception
     */
    public function __construct(array $config)
    {
        $name = trim($config['name']);
        if(empty($name)) {
            throw new Exception("队列名称不能为空");
        }

        $this->name = $name;
        $this->config = $config;

        $this->initConnection();
    }

    public function __destruct()
    {
        $this->adapter->close();
    }

    /**
     * 初始化队列连接
     *
     * @throws Zend_Exception
     */
    protected function initConnection()
    {
        $config = $this->config;
        if (empty($config['adapter']['class'])) {
            throw new Exception("没有指定队列适配器");
        }

        if (! is_subclass_of($config['adapter']['class'], AdapterIntrface::class)  ) {
            throw new Exception("错误的适配器类");
        }

        $this->adapter = new $config['adapter']['class'];
        $this->adapter->connect($config['adapter']);
    }

    /**
     * 取得队列名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 重新连接
     */
    public function reconnect()
    {
        $this->initConnection();
    }

    /**
     * 取得队列存储的全名
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->queueNamePrefix . $this->name;
    }

    /**
     * 设置名称前缀
     *
     * @param string $prefix    前缀
     * @return \LightQueueClient\Queue
     */
    public function setNamePrefix($prefix)
    {
        $this->queueNamePrefix = (string) $prefix;
        return $this;
    }

    /**
     * 取得队列信息
     *
     * @return array|string
     */
    public function info($key = null)
    {
        return $this->adapter->getStats($this->getFullName(), $key);
    }

    /**
     * 获取队列长度
     *
     * @return int
     */
    public function length()
    {
        return $this->adapter->length($this->getFullName());
    }

    /**
     * 查看队列数据（不会删除队列中数据）
     *
     * @param string    $name     队列名称
     * @param int       $offset   起始序号
     * @param int       $length   结束序号
     * @return array
     */
    public function view($offset, $length)
    {
        $messages = $this->adapter->view($this->getFullName(), $offset, $length);

        if(!empty($messages)) foreach($messages as & $msg) {
            $msg = self::messageUnserialize($msg);
        }

        return $messages;
    }

    /**
     * 向队列中写入一条消息
     *
     * @param Message $msg 一条队列消息
     * @return boolean
     */
    public function enqueue(Message $msg)
    {
        $enq = $this->adapter->enqueue($this->getFullName(), self::messageSerialize($msg));
        $this->adapter->increaseStats($this->getFullName(), 'enqueue');

        return $enq;
    }

    /**
     * 从队列中取一条消息
     *
     * @return Message | null
     */
    public function dequeue()
    {
        $msg = $this->adapter->dequeue($this->getFullName());

        if (!empty($msg)) {
            $this->adapter->increaseStats($this->getFullName(), 'dequeue');
            return self::messageUnserialize($msg);
        }
    }

    /**
     * 向队列退回一条消息（默认放在队头，可选放在队尾）
     *
     * @param Message   $msg        一条队列消息
     * @param boolean                       $toTail     是否放在队尾
     * @return boolean
     */
    public function refuse(Message $msg, $toTail = false)
    {
        $msg->refused();
        $refused = $this->adapter->enqueue($this->getFullName(), self::messageSerialize($msg), $toTail);
        $this->adapter->increaseStats($this->getFullName(), 'refuse');

        return $refused;
    }

    /**
     * 回收一条被抛弃的消息
     *
     * @param Message $msg  需要回收的消息
     * @return boolean
     */
    public function recycle(Message $msg)
    {
        $msg->clearRefuseCount();
        $this->adapter->increaseStats($this->getFullName(), 'recyle');
        return $this->getRecycleBin()->enqueue($msg);
    }

    /**
     * 获取回收站队列
     *
     * @return \LightQueueClient\Queue
     */
    public function getRecycleBin()
    {
        if (! $this->recycleBin) {
            $config = $this->config;
            $config['name'] .= '.recycle_bin';

            $this->recycleBin = new static($config);
        }

        return $this->recycleBin;
    }

    /**
     * 检查队列连接是否有效
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->adapter->isConnected();
    }
}
