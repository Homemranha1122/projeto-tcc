<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login']; // Pode ser email ou nome de usuário
    $senha = $_POST['senha'];

    // Verifica se o login é email ou nome de usuário
    $stmt = $conn->prepare("SELECT id, nome, senha, is_admin FROM usuarios WHERE email = ? OR usuario = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['is_admin'] = (bool)$user['is_admin']; 
            
            header('Location: index.php');
            exit();
        } else {
            $erro = 'Nome de usuário/email ou senha inválidos.';
        }
    } else {
        $erro = 'Nome de usuário/email ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Olimclima</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .form-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #3498db; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2980b9; }
        .error { background: #e74c3c; color: white; padding: 10px; text-align: center; border-radius: 4px; margin-bottom: 15px; }
        .link { text-align: center; margin-top: 15px; }
        .link a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <?php if ($erro): ?><div class="error"><?= $erro ?></div><?php endif; ?>
        <form method="POST" action="login.php" novalidate>
            <div class="input-group">
                <label for="login">Email ou Nome de Usuário</label>
                <input type="text" name="login" id="login" required>
            </div>
            <div class="input-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        <div class="link">
            <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>