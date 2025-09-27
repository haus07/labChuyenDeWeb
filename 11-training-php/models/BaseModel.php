<?php
require_once 'configs/database.php';

abstract class BaseModel {
    protected static $_connection;

    public function __construct() {
        if (!isset(self::$_connection)) {
            mysqli_report(MYSQLI_REPORT_OFF); // turn off noisy warnings
            self::$_connection = mysqli_connect(
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME,
                DB_PORT
            );

            if (mysqli_connect_errno()) {
                // In dev bạn có thể log, prod nên handle khác
                die("Connect failed: " . mysqli_connect_error());
            }

            // ensure proper charset
            mysqli_set_charset(self::$_connection, 'utf8mb4');
        }
    }

    /* -------------------------
       Low-level helpers
       ------------------------- */

    // Execute a prepared statement. $types is string like 'si', $params is array of values
    protected function executePrepared(string $sql, ?string $types, ?array $params) {
        $stmt = self::$_connection->prepare($sql);
        if ($stmt === false) {
            // optional: log mysqli_error(self::$_connection)
            return false;
        }

        if ($types && $params) {
            // mysqli expects references
            $refs = [];
            foreach ($params as $k => $v) $refs[$k] = &$params[$k];
            array_unshift($refs, $types);
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $ok = $stmt->execute();
        if ($ok === false) {
            // optional: log $stmt->error
            $stmt->close();
            return false;
        }

        return $stmt;
    }

    /* -------------------------
       Select / Query helpers
       - If $types is null => run raw query (backwards compat)
       - If $types provided => use prepared
       ------------------------- */

    protected function select(string $sql, ?string $types = null, ?array $params = null): array {
        if ($types === null) {
            // backward-compatible raw query (use only for trusted queries)
            $result = self::$_connection->query($sql);
            $rows = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            return $rows;
        } else {
            $stmt = $this->executePrepared($sql, $types, $params);
            if ($stmt === false) return [];
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        }
    }

    // Single row fetch (prepared)
    protected function selectOne(string $sql, ?string $types = null, ?array $params = null): ?array {
        $rows = $this->select($sql, $types, $params);
        return !empty($rows) ? $rows[0] : null;
    }

    /* -------------------------
       Insert / Update / Delete
       ------------------------- */

    protected function insertPrepared(string $sql, string $types, array $params) {
        $stmt = $this->executePrepared($sql, $types, $params);
        if ($stmt === false) return false;
        $insertId = self::$_connection->insert_id;
        $stmt->close();
        return $insertId;
    }

    protected function updatePrepared(string $sql, string $types, array $params) {
        $stmt = $this->executePrepared($sql, $types, $params);
        if ($stmt === false) return false;
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    protected function deletePrepared(string $sql, string $types, array $params) {
        return $this->updatePrepared($sql, $types, $params);
    }

    /* -------------------------
       Legacy raw wrappers (DEPRECATED)
       - Keep them so older code doesn't break immediately,
       - But please migrate to prepared APIs above.
       ------------------------- */

    protected function query(string $sql) {
        return self::$_connection->query($sql);
    }

    protected function delete(string $sql) {
        return $this->query($sql);
    }

    protected function update(string $sql) {
        return $this->query($sql);
    }

    protected function insert(string $sql) {
        return $this->query($sql);
    }
}
