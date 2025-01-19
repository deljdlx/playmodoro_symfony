<?php

namespace App\Controller;

use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouTubeController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->apiKey = $_ENV['YOUTUBE_API_KEY'] ?? '';
    }

    #[Route('/api/video/{videoId}', methods: ['GET'])]
    public function getVideoInfo(string $videoId): JsonResponse
    {
        // 1️⃣ Vérifier si la vidéo est déjà en base de données
        $videoRepository = $this->entityManager->getRepository(Video::class);
        $existingVideo = $videoRepository->findOneBy(['api_id' => $videoId]);

        if ($existingVideo) {
            return $this->json([
                'api_id' => $existingVideo->getApiId(),
                'data' => $existingVideo->getData(),
                'source' => 'database' // Indique que les données viennent de la BDD
            ]);
        }

        // 2️⃣ Si la vidéo n'est pas en base, récupérer les infos depuis l'API YouTube
        $url = "https://www.googleapis.com/youtube/v3/videos";
        $params = [
            'query' => [
                'part' => 'snippet,statistics',
                'id' => $videoId,
                'key' => $this->apiKey
            ]
        ];

        $response = $this->httpClient->request('GET', $url, $params);
        $data = $response->toArray();

        if (empty($data['items'])) {
            return $this->json(['error' => 'Vidéo introuvable'], 404);
        }

        $videoData = $data['items'][0];

        // 3️⃣ Stocker la vidéo en base de données
        $video = new Video();
        $video->setApiId($videoId);
        $video->setData($videoData);
        $video->setSource('youtube-api');

        $this->entityManager->persist($video);
        $this->entityManager->flush();

        // 4️⃣ Retourner les données au client
        return $this->json([
            'api_id' => $video->getApiId(),
            'data' => $video->getData(),
            'source' => 'youtube-api' // Indique que les données viennent de l'API YouTube
        ]);
    }
}
