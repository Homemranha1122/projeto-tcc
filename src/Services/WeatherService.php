<?php

namespace App\Services;

/**
 * Serviço para obter previsão do tempo via OpenWeatherMap API
 * 
 * Implementa cache simples por request para evitar chamadas duplicadas
 */
class WeatherService
{
    private $apiKey;
    private $cache = [];

    /**
     * Construtor
     * 
     * @param string|null $apiKey Chave da API (usa env se não fornecida)
     */
    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey ?? env('OPENWEATHER_API_KEY', '5a3a2e0c72f5e5c8d2e2f3e2c6e2b7ac');
    }

    /**
     * Obtém previsão do tempo para uma cidade
     * 
     * @param string $cidade Nome da cidade
     * @param string $uf Sigla do estado (opcional)
     * @return array Dados da previsão
     */
    public function obterPrevisao($cidade, $uf = '')
    {
        $cacheKey = strtolower($cidade . '_' . $uf);
        
        // Retorna do cache se disponível
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $resultado = $this->buscarPrevisao($cidade, $uf);
        
        // Armazena no cache
        $this->cache[$cacheKey] = $resultado;
        
        return $resultado;
    }

    /**
     * Busca previsão do tempo via API
     * 
     * @param string $cidade
     * @param string $uf
     * @return array
     */
    private function buscarPrevisao($cidade, $uf)
    {
        // Busca coordenadas via geocoding OpenWeatherMap
        $geoUrl = sprintf(
            'http://api.openweathermap.org/geo/1.0/direct?q=%s&limit=1&appid=%s',
            urlencode($cidade . ($uf ? ",$uf,BR" : ',BR')),
            $this->apiKey
        );
        
        $geoJson = @file_get_contents($geoUrl);
        $geo = json_decode($geoJson, true);

        if (!$geo || !isset($geo[0]['lat']) || !isset($geo[0]['lon'])) {
            return $this->erroResposta($cidade, $uf, 'Local não encontrado');
        }

        $lat = $geo[0]['lat'];
        $lon = $geo[0]['lon'];

        // Busca previsão atual
        $weatherUrl = sprintf(
            'https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&units=metric&lang=pt_br&appid=%s',
            $lat,
            $lon,
            $this->apiKey
        );
        
        $weatherJson = @file_get_contents($weatherUrl);
        $w = json_decode($weatherJson, true);

        if (!$w || !isset($w['main']) || !isset($w['weather'][0])) {
            return $this->erroResposta($cidade, $uf, 'Previsão indisponível');
        }

        return [
            'erro' => false,
            'cidade' => $cidade,
            'uf' => $uf,
            'lat' => $lat,
            'lng' => $lon,
            'temp' => round($w['main']['temp']),
            'umidade' => $w['main']['humidity'],
            'descricao' => ucfirst($w['weather'][0]['description']),
            'icone_url' => "https://openweathermap.org/img/wn/" . $w['weather'][0]['icon'] . "@2x.png",
            'vento' => round($w['wind']['speed'] * 3.6), // m/s para km/h
            'precipitacao' => isset($w['rain']['1h']) ? $w['rain']['1h'] : 0
        ];
    }

    /**
     * Retorna array padrão de erro
     * 
     * @param string $cidade
     * @param string $uf
     * @param string $mensagem
     * @return array
     */
    private function erroResposta($cidade, $uf, $mensagem)
    {
        return [
            'erro' => true,
            'cidade' => $cidade,
            'uf' => $uf,
            'descricao' => $mensagem,
            'icone_url' => 'https://openweathermap.org/img/wn/01d@2x.png'
        ];
    }

    /**
     * Limpa o cache
     * 
     * @return void
     */
    public function limparCache()
    {
        $this->cache = [];
    }
}
