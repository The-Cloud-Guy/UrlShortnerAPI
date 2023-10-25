<?php

namespace App\Controller;

use App\Entity\Urls;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UrlApiController extends AbstractController
{
    private EntityManagerInterface $em;
    public const SHORT_URL_LENGTH = 9;
    public const RANDOM_BYTES = 32;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Genarete and stores a long and short URL combination in the database
     *
     * @param Request $request
     * @return Response
     */

     #[Route('/api/v1/getshorturl/{url}', name: 'generate_short_url', methods: ["GET","POST"])]
     public function generateShortUrl(string $url): JsonResponse
     {
         $shortenedUrlValue = "";
         $originalUrl = $url;
         if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['Error Message' => $originalUrl. " is unfortunately not a valid URL", 'Status Code' => Response::HTTP_BAD_REQUEST]);
         } 
        
 
         if($this->hastShortUrl($originalUrl)) {
             $shortenedUrlValue = $this->getShortUrl($originalUrl);
         }else{
             $shortenedUrlValue = $this->shortenUrlGenerator();
             $this->persistUrl($originalUrl, $shortenedUrlValue);
         }    
 
         return new JsonResponse(['Shortened Url' => "tinyurl.com/".$shortenedUrlValue, 'Status Code' => Response::HTTP_OK]);
     }

    
    
    /**
     * Retrieves a long URL using the short one provided
     *
     * @param string $shortUrl
     * @return string
     */
    public function getLongUrl(string $shortUrl): string
    {
        $url = $this->em->getRepository(Urls::class)->findOneBy(['url'=> $shortUrl]);

        return $url->getOriginalUrl();

    }
    
    /**
     * Checks if a short URL exists
     *
     * @param string $originalUrl
     * @return boolean
     */
    public function hastShortUrl(string $originalUrl): bool
    {
        $url = $this->em->getRepository(Urls::class)->findOneBy(['originalUrl'=> $originalUrl]);

        return !empty($url)? true : false;
    }

    /**
     * Return a short Url from the database
     *
     * @param string $originalUrl
     * @return string
     */
    public function getShortUrl(string $originalUrl): string
    {
        $url = $this->em->getRepository(Urls::class)->findOneBy(['originalUrl'=> $originalUrl]);

        return $url->getShortUrl();
    }

     /**
     * Generate a 9-character short URL
     *
     * @return string
     */
    protected function shortenUrlGenerator(): string
    {
        $shortenedUrl = substr(
            base64_encode(
                sha1(
                    uniqid(
                        random_bytes(self::RANDOM_BYTES),
                        true
                    )
                )
            ),
            0,
            self::SHORT_URL_LENGTH
        );

        return $shortenedUrl;
    }

    /**
     * Inserts a new long and short URL combination into the urls table.
     *
     * @param string $longUrl
     * @param string $shortenedUrl
     * @return void
     */
    public function persistUrl(string $originalUrl, string $shortenedUrl): void
    {
        $url = new Urls();

        $url->setShortUrl($shortenedUrl);
        $url->setOriginalUrl($originalUrl);

        $this->em->persist($url);
        $this->em->flush();
        
    }

    
    
    

    
    
   


}
