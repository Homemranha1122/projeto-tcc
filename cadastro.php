<?php
// Inicia a sessão apenas se não houver uma ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Usa include_once para evitar redeclaração de funções
include_once 'conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $usuario = $_POST['usuario']; // Novo campo para nome de usuário
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];

    if (empty($nome) || empty($email) || empty($usuario) || empty($senha)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif ($senha !== $senha_confirm) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        // Verifica se o email já existe
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        // Verifica se o nome de usuário já existe
        $stmt_check_user = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check_user->bind_param("s", $usuario);
        $stmt_check_user->execute();
        $result_check_user = $stmt_check_user->get_result();

        if ($result_check->num_rows > 0) {
            $erro = 'Este email já está cadastrado.';
        } elseif ($result_check_user->num_rows > 0) {
            $erro = 'Este nome de usuário já está em uso.';
        } else {
            $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt_insert = $conn->prepare("INSERT INTO usuarios (nome, email, usuario, senha) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $nome, $email, $usuario, $hashed_password);
            
            if ($stmt_insert->execute()) {
                $sucesso = 'Cadastro realizado com sucesso! Você já pode fazer o login.';
                
                // Redireciona para o login após 2 segundos
                header("Refresh: 2; URL=login.php");
            } else {
                $erro = 'Ocorreu um erro ao tentar cadastrar: ' . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        $stmt_check_user->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Olimclima</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;}
        .form-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #27ae60; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #229954; }
        .message { color: white; padding: 10px; text-align: center; border-radius: 4px; margin-bottom: 15px; }
        .error { background: #e74c3c; }
        .success { background: #27ae60; }
        .link { text-align: center; margin-top: 15px; }
        .link a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Cadastre-se</h2>
        <?php if ($erro): ?><div class="message error"><?= $erro ?></div><?php endif; ?>
        <?php if ($sucesso): ?><div class="message success"><?= $sucesso ?></div><?php endif; ?>
        <form method="POST" action="cadastro.php" novalidate>
            <div class="input-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="usuario">Nome de Usuário</label>
                <input type="text" name="usuario" id="usuario" required>
            </div>
            <div class="input-group">
                <label for="senha">Senha (mín. 6 caracteres)</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <div class="input-group">
                <label for="senha_confirm">Confirme a Senha</label>
                <input type="password" name="senha_confirm" id="senha_confirm" required>
            </div>
            <button type="submit">Cadastrar</button>
        </form>
        <div class="link">
            <p>Já tem uma conta? <a href="login.php">Faça o login</a></p>
        </div>
    </div>
</body>
</html>