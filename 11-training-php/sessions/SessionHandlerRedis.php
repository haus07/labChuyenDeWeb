<?php
class SessionHandlerRedis implements SessionHandlerInterface {
    private $redis;

    public function __construct($redis) {
        $this->redis = $redis;
    }

    public function open(string $savePath, string $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $data = $this->redis->get("PHPSESSID:$id");
        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool {
        return $this->redis->set("PHPSESSID:$id", $data);
    }

    public function destroy(string $id): bool {
        return $this->redis->del("PHPSESSID:$id") > 0;
    }

    public function gc(int $max_lifetime): int|false {
        // Redis tự hết hạn key, nên không cần làm gì
        return true;
    }
}

