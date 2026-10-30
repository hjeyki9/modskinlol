<?php
declare(strict_types=1);

// ============================================================
// CẤU HÌNH KẾT NỐI SUPABASE
// ============================================================
// Lấy các thông tin dưới đây từ Supabase Dashboard:
// Mở project → nhấn nút "Connect" (góc trên) → chọn tab "Session pooler"
// hoặc "Direct connection" → copy connection string và tách ra.
//
// Ví dụ connection string dạng URI:
//   postgresql://postgres:[YOUR-PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres
// hoặc qua pooler:
//   postgres://postgres.[PROJECT-REF]:[YOUR-PASSWORD]@aws-0-[REGION].pooler.supabase.com:5432/postgres

$DB_HOST    = 'aws-0-eu-west-1.pooler.supabase.com';
$DB_PORT    = '5432';
$DB_NAME    = 'postgres';
$DB_USER    = 'postgres.vzghjglfzjmspzhxqobw';   // Lưu ý: có hậu tố .vzghjglfzjmspzhxqobw
$DB_PASS    = 'Hungnhankhang9';                  // ⚠️ THAY BẰNG MẬT KHẨU THẬT
$DB_SSLMODE = 'require';


// ============================================================
// KẾT NỐI DATABASE BẰNG PDO
// ============================================================
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $DB_HOST,
    $DB_PORT,
    $DB_NAME,
    $DB_SSLMODE
);

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Lỗi kết nối database: ' . $e->getMessage());
}

// ============================================================
// XỬ LÝ ĐĂNG KÝ (KHI NHẤN NÚT)
// ============================================================
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($fullName === '' || $email === '') {
        $message = 'Vui lòng nhập đầy đủ họ tên và email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email không hợp lệ.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO members (full_name, email) VALUES (:full_name, :email)'
            );
            $stmt->execute([
                ':full_name' => $fullName,
                ':email'     => $email,
            ]);
            $message = 'Đăng ký thành công!';
        } catch (PDOException $e) {
            // Lỗi 23505 = unique_violation (email đã tồn tại)
            if ($e->getCode() === '23505') {
                $message = 'Email này đã được đăng ký.';
            } else {
                $message = 'Lỗi đăng ký: ' . $e->getMessage();
            }
        }
    }
}

// ============================================================
// LẤY DANH SÁCH THÀNH VIÊN
// ============================================================
$members = $pdo->query(
    'SELECT id, full_name, email, created_at FROM members ORDER BY created_at DESC'
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Test Supabase - Danh sách thành viên</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 16px; }
        h1, h2 { color: #333; }
        .message { padding: 10px; margin: 16px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error   { background: #f8d7da; color: #721c24; }
        form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 32px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 8px; margin-bottom: 12px;
            border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        button {
            background: #3ecf8e; color: #fff; border: none;
            padding: 10px 20px; border-radius: 4px; cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #2fb87a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f1f3f5; }
    </style>
</head>
<body>

<h1>Test kết nối Supabase</h1>

<?php if ($message !== ''): ?>
    <div class="message <?= str_contains($message, 'thành công') ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- FORM ĐĂNG KÝ (CHỈ ĐỂ TEST) -->
<h2>Đăng ký thành viên</h2>
<form method="post" action="">
    <label for="full_name">Họ và tên</label>
    <input type="text" id="full_name" name="full_name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <button type="submit" name="register" value="1">Đăng ký</button>
</form>

<!-- DANH SÁCH THÀNH VIÊN -->
<h2>Danh sách thành viên (<?= count($members) ?>)</h2>

<?php if (empty($members)): ?>
    <p>Chưa có thành viên nào. Hãy đăng ký thử!</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Thời gian đăng ký</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $index => $m): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($m['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>