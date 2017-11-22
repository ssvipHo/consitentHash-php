<?php

class BaseConsistentHash
{
    public $nodes = [];
    public $tempNodes = [];
    public $replica = 0;
    public $count = [];

    /**
     * BaseConsistentHash constructor.
     *
     * @param int $replica
     */
    public function __construct(int $replica = 0)
    {
        $this->replica = $replica;
    }

    /**
     * add a node
     *
     * @param $node
     *
     * @return bool
     */
    public function addNode($node): bool
    {
        if (empty($node))
        {
            return false;
        }

        for ($i = 0; $i < $this->replica; $i++)
        {
            $nodeKey = $this->hash($node . $i);
            $this->nodes[$node][] = $nodeKey;
            $this->tempNodes[$nodeKey] = $node;
        }

        return true;
    }

    /**
     * get node
     *
     * @param string $cacheStr
     *
     * @return array|bool|mixed
     */
    public function findNode(string $cacheStr)
    {
        if (empty($cacheStr))
        {
            return false;
        }

        // get the key from self hash method
        $cacheKey = $this->hash($cacheStr);

        // sort
        ksort($this->tempNodes);

        foreach (array_keys($this->tempNodes) as $key)
        {
            if ($cacheKey <= $key)
            {
                $this->count[$this->tempNodes[$key]]++;
                //                echo '{' . $this->tempNodes[$key] . '}' . PHP_EOL;
                return $this->tempNodes[$key];
            }
        }
        $this->count[current($this->tempNodes)]++;
        return current($this->tempNodes);
    }

    /**
     * return hash key
     *
     * @param string $str
     *
     * @return int
     */
    public function hash(string $str): int
    {
        // use time33
        // hash(i) = hash(i - 1) * 33 + str[i]

        if (0 >= mb_strlen($str))
        {
            return 0;
        }
        $str = md5($str);
        $hash = 0;

        for ($i = 0; $i < 32; $i++)
        {
            $hash += ($hash << 5) + ord($str{$i});
        }

        return $hash & 0x7fffffff;
    }
}

$example = new BaseConsistentHash(1000);

$example->addNode('192.168.0.1');
$example->addNode('192.168.0.2');
$example->addNode('192.168.0.3');

//// test 10000 data
for ($i = 0; $i < 100000; $i++)
{
    $example->findNode(str_replace('.', '', microtime(true)));
}

echo '<pre>';
print_r($example->count);
echo '</pre>';

$example->addNode('192.168.0.4');

// test 10000 data
for ($i = 0; $i < 100000; $i++)
{
    $example->findNode(str_replace('.', '', microtime(true)));
}
echo '<pre>';
print_r($example->count);
echo '</pre>';

