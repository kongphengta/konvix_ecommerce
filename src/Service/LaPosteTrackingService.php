<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LaPosteTrackingService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Récupère le statut d'un colis via l'API La Poste
     * @param string $trackingNumber
     * @return array|null
     */
    public function getTrackingStatus(string $trackingNumber): ?array
    {
        $url = sprintf('https://api.laposte.fr/suivi/v2/idships/%s', $trackingNumber);
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'X-Okapi-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);
            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
        } catch (\Exception $e) {
            // Log ou gestion d'erreur
        }
        return null;
    }
}
