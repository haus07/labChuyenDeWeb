<?php

require_once 'BaseModel.php';

class UserModel extends BaseModel {

    // Helper: fetch all
    protected function fetchAllFromStmt($stmt) {
        $res = $stmt->get_result();
        if (!$res) return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // Helper: fetch one
    protected function fetchOneFromStmt($stmt) {
        $res = $stmt->get_result();
        if (!$res) return null;
        return $res->fetch_assoc();
    }

    public function findUserById($id) {
        $sql = 'SELECT * FROM users WHERE id = ? LIMIT 1';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $this->fetchOneFromStmt($stmt);
        $stmt->close();
        return $user ? [$user] : [];
    }

    public function findUser($keyword) {
        $like = '%' . $keyword . '%';
        $sql = 'SELECT * FROM users WHERE user_name LIKE ? OR user_email LIKE ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $users = $this->fetchAllFromStmt($stmt);
        $stmt->close();
        return $users;
    }

    /**
     * Authentication user
     * @param $userName
     * @param $password
     * @return array|null
     */
    public function auth($userName, $password) {
        $sql = 'SELECT * FROM users WHERE name = ? LIMIT 1';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('s', $userName);
        $stmt->execute();
        $user = $this->fetchOneFromStmt($stmt);
        $stmt->close();

        if (!$user) {
            return null;
        }

        $stored = $user['password'] ?? '';

        // 1) Preferred: stored as password_hash
        if (password_verify($password, $stored)) {
            return [$user];
        }

        // 2) Fallback: if DB still uses md5, accept md5 and rehash to stronger
        if ($stored === md5($password)) {
            // Rehash and update DB
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = 'UPDATE users SET password = ? WHERE id = ?';
            $uStmt = self::$_connection->prepare($updateSql);
            $uStmt->bind_param('si', $newHash, $user['id']);
            $uStmt->execute();
            $uStmt->close();

            // Update returned user password value
            $user['password'] = $newHash;
            return [$user];
        }

        // not authenticated
        return null;
    }

    /**
     * Delete user by id
     * @param $id
     * @return bool
     */
    public function deleteUserById($id) {
        $sql = 'DELETE FROM users WHERE id = ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Update user
     * @param $input
     * @return bool
     */
    public function updateUser($input) {
        $id = (int)($input['id'] ?? 0);
        $name = $input['name'] ?? '';

        if (isset($input['password']) && $input['password'] !== '') {
            $passHash = password_hash($input['password'], PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET name = ?, password = ? WHERE id = ?';
            $stmt = self::$_connection->prepare($sql);
            $stmt->bind_param('ssi', $name, $passHash, $id);
        } else {
            $sql = 'UPDATE users SET name = ? WHERE id = ?';
            $stmt = self::$_connection->prepare($sql);
            $stmt->bind_param('si', $name, $id);
        }

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Insert user
     * @param $input
     * @return int|false new insert id or false
     */
    public function insertUser($input) {
        $name = $input['name'] ?? '';
        $password = $input['password'] ?? '';
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO users (name, password) VALUES (?, ?)';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $name, $passHash);
        $ok = $stmt->execute();
        $insertId = $ok ? self::$_connection->insert_id : false;
        $stmt->close();
        return $insertId;
    }

    /**
     * Search users
     * @param array $params
     * @return array
     */
    public function getUsers($params = []) {
        if (!empty($params['keyword'])) {
            $keyword = '%' . $params['keyword'] . '%';
            $sql = 'SELECT * FROM users WHERE name LIKE ?';
            $stmt = self::$_connection->prepare($sql);
            $stmt->bind_param('s', $keyword);
            $stmt->execute();
            $users = $this->fetchAllFromStmt($stmt);
            $stmt->close();
        } else {
            $sql = 'SELECT * FROM users';
            $stmt = self::$_connection->prepare($sql);
            $stmt->execute();
            $users = $this->fetchAllFromStmt($stmt);
            $stmt->close();
        }

        return $users;
    }
}
