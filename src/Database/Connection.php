<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Classe singleton para gerenciar conexão PDO com o banco de dados
 * 
 * Esta classe substitui gradualmente o uso direto de mysqli
 * mantendo compatibilidade com a estrutura existente.
 */
class Connection
{
    private static $instance = null;
    private $pdo;

    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct()
    {
        $host = env('DB_HOST', 'localhost');
        $dbname = env('DB_NAME', 'enchentes');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException("Erro ao conectar ao banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Previne clonagem do objeto
     */
    private function __clone() {}

    /**
     * Previne deserialização do objeto
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Retorna a instância única da conexão
     * 
     * @return Connection
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna o objeto PDO
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Atalho para executar queries diretas (usar com cuidado)
     * 
     * @param string $query
     * @return \PDOStatement
     */
    public function query($query)
    {
        return $this->pdo->query($query);
    }

    /**
     * Prepara uma query
     * 
     * @param string $query
     * @return \PDOStatement
     */
    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }
}
