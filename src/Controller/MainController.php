<?php

namespace App\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route(
 *     "/{_locale}",
 *     requirements={
 *         "_locale": "en|es",
 *     }
 * )
 */
class MainController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $examples = [
            [
                'code' => 'examples/example1/code.html.twig',
                'result' => 'examples/example1/result.html.twig',
            ],
        ];

        return $this->render('page-index.html.twig', compact('examples'));
    }

    /**
     * @Route("/doc", name="doc_index")
     */
    public function docIndex(): Response
    {
        return $this->redirectToRoute('doc', [
            'file' => 'introduction.md',
        ]);
    }

    /**
     * @Route("/doc/{file<.+>}", name="doc")
     */
    public function doc(string $_locale, string $file, MarkdownParserInterface $parser): Response
    {
        $docDir = __DIR__.'/../../pyramidal-tests/doc';
        $filename = $docDir."/{$_locale}/{$file}";

        if (! file_exists($filename)) {
            throw new NotFoundHttpException();
        }

        $fileInfo = pathinfo($filename);

        if ('md' != $fileInfo['extension']) {
            return new BinaryFileResponse($filename);
        }

        $updateLinks = function (string $html) use ($_locale): string {
            $crawler = new Crawler($html);

            foreach ($crawler->filter('a') as $anchorNode) {
                $hrefItem = $anchorNode->attributes->getNamedItem('href');
                $linkAddress = $hrefItem->value;

                if (! preg_match('/^http/', $linkAddress)) {
                    $hrefItem->value = $this->generateUrl(
                        'doc',
                        [
                            '_locale' => $_locale,
                            'file' => $hrefItem->value,
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }
            }

            return $crawler->html();
        };

        $mainMenu = $updateLinks($parser->transformMarkdown(file_get_contents($docDir."/{$_locale}/index.md")));
        $content = $updateLinks($parser->transformMarkdown(file_get_contents($filename)));

        $secondaryMenu = [];
        $contentCrawler = new Crawler($content);

        $replacements = [
            '.' => '',
            ' ' => '-',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ];

        foreach ($contentCrawler->filter('h2, h3, h4, h5, h6') as $node) {
            $slug = str_replace(
                array_keys($replacements),
                array_values($replacements),
                strtolower($node->textContent)
            );

            $node->setAttribute('id', $slug);

            $secondaryMenu[] = [
                'text' => $node->textContent,
                'slug' => $slug,
            ];
        }

        $content = $contentCrawler->html();

        return $this->render('page-doc.html.twig', [
            'mainMenu' => $mainMenu,
            'content' => $content,
            'secondaryMenu' => $secondaryMenu,
        ]);
    }
}
