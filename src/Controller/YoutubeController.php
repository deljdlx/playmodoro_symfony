<?php

namespace App\Controller;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeController extends AbstractController
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
        $mediaRepository = $this->entityManager->getRepository(Media::class);
        $existingVideo = $mediaRepository->findOneBy(['api_id' => $videoId]);

        if ($existingVideo) {
            $response = $this->json([
                'api_id' => $existingVideo->getApiId(),
                'data' => $existingVideo->getData(),
                'source' => 'database' // Indique que les données viennent de la BDD
            ]);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

            return $response;
        }


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

        $video = new Media();
        $video->setType('youtube-video');
        $video->setApiId($videoId);
        $video->setData($videoData);
        $video->setSource('youtube-api');

        $this->entityManager->persist($video);
        $this->entityManager->flush();


        $response = $this->json([
            'api_id' => $video->getApiId(),
            'data' => $video->getData(),
            'source' => 'youtube-api'
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }

    #[Route('/api/playlist/{playlistId}', methods: ['GET'])]
    public function getPlaylistInfo(string $playlistId): JsonResponse
    {
        $mediaRepository = $this->entityManager->getRepository(Media::class);
        $existingPlaylist = $mediaRepository->findOneBy(['api_id' => $playlistId]);

        if ($existingPlaylist) {
            $response = $this->json([
                'api_id' => $existingPlaylist->getApiId(),
                'data' => $existingPlaylist->getData(),
                'source' => 'database' // Indique que les données viennent de la BDD
            ]);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

            return $response;
       }

        $url = sprintf(
            "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=%s&maxResults=50&key=%s",
            $playlistId,
            $this->apiKey
        );

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        // save playlist in database
        $playlist = new Media();
        $playlist->setType('youtube-playlist');
        $playlist->setApiId($playlistId);
        $playlist->setData($data);
        $playlist->setSource('youtube-api');

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();

        foreach ($data['items'] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'];
            $existingVideo = $mediaRepository->findOneBy(['api_id' => $videoId]);
            if ($existingVideo) {
                continue;
            }

            $video = new Media();
            $video->setType('youtube-video');
            $video->setApiId($videoId);
            $video->setData($item);
            $video->setSource('youtube-api');

            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }

        $response = $this->json([
            'api_id' => $playlist->getApiId(),
            'data' => $data,
            'source' => 'youtube-api'
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }


    #[Route('/api/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        $query = trim($query);

        $noCache = $request->query->get('noCache');

        if (!$query) {
            return $this->json(['error' => 'Aucun mot-clé fourni'], 400);
        }


        // normalize query
        $slug = $query;
        $slug = mb_strtolower($slug);
        $slug = preg_replace('/\s+/', '-', $slug);


        $mediaRepository = $this->entityManager->getRepository(Media::class);
        $existingSearch = $mediaRepository->findOneBy(['api_id' => $slug]);
        if ($existingSearch && !$noCache) {
            $response = $this->json([
                'api_id' => $existingSearch->getApiId(),
                'data' => $existingSearch->getData(),
                'source' => 'database' // Indique que les données viennent de la BDD
            ]);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

            return $response;
        }



        $url = "https://www.googleapis.com/youtube/v3/search";
        $params = [
            'query' => [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'video,playlist',
                'maxResults' => 100,
                'key' => $this->apiKey,
            ]
        ];

        $response = $this->httpClient->request('GET', $url, $params);
        if ($response->getStatusCode() !== 200) {
            return $this->json(['error' => 'Erreur lors de la requête à l\'API YouTube'], 500);
        }

        $data = $response->toArray();

        if(!$data) {
            return $this->json(['error' => 'Aucun résultat trouvé'], 404);
        }

        $search = new Media();
        $search->setType('youtube-search');
        $search->setApiId($slug);
        $search->setData($data);
        $search->setSource('youtube-api');
        $search->setType('youtube-search');

        $this->entityManager->persist($search);
        $this->entityManager->flush();

        foreach ($data['items'] as $item) {

            if($item['id']['kind'] === ' youtube#playlist') {
                $videoId = $item['id']['playlistId'];
            }
            elseif($item['id']['kind'] === 'youtube#video') {
                $videoId = $item['id']['videoId'];
            }
            else {
                continue;
            }

            $existingVideo = $mediaRepository->findOneBy(['api_id' => $videoId]);
            if ($existingVideo) {
                continue;
            }

            $video = new Media();
            $video->setType('youtube-video');
            $video->setApiId($videoId);
            $video->setData($item);
            $video->setSource('youtube-api');

            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }

        $response = $this->json([
            'api_id' => $search->getApiId(),
            'data' => $data,
            'source' => 'youtube-api'
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;

    }


}
