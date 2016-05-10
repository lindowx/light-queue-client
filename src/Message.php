<?php
namespace LightQueueClient;

class Message
{
    /**
     * 退回次数
     *
     * @var int
     */
    protected $refuseCount = 0;

    /**
     *
     * @var array
     */
    protected $data = [];

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        if (! isset($this->data[$key])) {
            throw new Exception("队列消息中不存在指定属性");
        }

        return $this->data[$key];
    }

    /**
     * 重置退回计数
     *
     * @return null
     */
    public function clearRefuseCount()
    {
        $this->refuseCount = 0;
    }

    /**
     * 累计退回次数
     */
    public function refused()
    {
        $this->refuseCount ++;
    }

    /**
     * 取得退回计数
     *
     * @return int
     */
    public function refusedCount()
    {
        return $this->refuseCount;
    }

    /**
     * 通过数组设置数据
     *
     * @param array $assoc 数组数据
     * @return null
     */
    public function initByArray(array $assoc)
    {
        if (! empty($assoc))
            foreach ($assoc as $prop => $value) {
                $this->{$prop} = $value;
            }
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * 数组化
     * @return array
     */
    public function toArray()
    {
        return [
            '__lqcmc' => static::class,
            'refuseCount' => $this->refuseCount,
            'data' => $this->data,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
