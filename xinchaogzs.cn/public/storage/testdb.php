<?php
// $host = '8.140.59.224';
$host = '127.0.0.1';
$port = '3306'; // 例如 3306
$username = 'xinchaogzs';
$password = 'p2kPFt47yw5Yk7xb';
$database = 'xinchaogzs';

// $dsn = "mysql:host=$host;port=$port;dbname=$database";
// try {
//     $pdo = new PDO($dsn, $username, $password);
//     echo "数据库连接成功。";
// } catch (PDOException $e) {
//     die("数据库连接失败：" . $e->getMessage());
// }


$dsn = "mysql:host=$host;port=$port;dbname=$database";
try {
    $pdo = new PDO($dsn, $username, $password);
    // 设置 PDO 错误模式为异常
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 准备 SQL 查询语句
    $sql = "SELECT * FROM do_admin";
    
    // 执行查询
    $stmt = $pdo->query($sql);
    
    // 获取所有结果
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 检查是否有数据返回
    if ($results) {
        echo "查询成功，共 " . count($results) . " 条记录：";
        foreach ($results as $row) {
            // 打印每条记录
            echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", password: " . $row['password'] . "<br/>";
            // 根据实际的列名来打印数据
        }
    } else {
        echo "没有找到数据。";
    }
} catch (PDOException $e) {
    die("数据库连接或查询失败：" . $e->getMessage());
}
?>