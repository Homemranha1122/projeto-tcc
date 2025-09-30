/**
 * Escapa HTML para output seguro
 * 
 * @param mixed $value
 * @return string
 */
function e($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Escapa HTML e remove tags (sanitização mais agressiva)
 * 
 * @param mixed $value
 * @return string
 */
function es($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars(strip_tags((string)$value), ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona para uma URL
 * 
 * @param string $url
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Obtém uma variável de ambiente
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Conversão de valores especiais
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }
    
    return $value;
}
