<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ColissimoShippingService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Crée une expédition Colissimo et retourne le numéro de suivi
     * @param array $shipmentData (adresse, poids, etc.)
     * @return string|null Numéro de suivi ou null en cas d'échec
     */
    public function createShipment(array $shipmentData): ?string
    {
        $url = 'https://api.laposte.fr/springcolissimo/v1/shipping-label'; // test sans /sandbox/
        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'X-Okapi-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $shipmentData,
            ]);
            // Récupère la réponse brute (JSON ou texte)
            $content = $response->getContent(false);
            // Stocke la réponse brute dans la session pour debug (sans session_start inutile)
            if (isset($_SESSION)) {
                $_SESSION['colissimo_api_debug'] = $content;
            }
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray(false); // ne lève pas d'exception sur erreur JSON
                // Le numéro de suivi est souvent dans $data['parcelNumber'] ou similaire
                return $data['parcelNumber'] ?? null;
            }
        } catch (\Exception $e) {
            if (isset($_SESSION)) {
                $_SESSION['colissimo_api_debug'] = $e->getMessage();
            }
        }
        return null;
    }
}
