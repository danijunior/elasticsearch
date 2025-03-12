<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Repository\CourseRepository;
use Elastica\Multi\Search;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchPhrase;
use Elastica\Query\MatchQuery;
use Elastica\Query\Range;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly PaginatedFinderInterface $finder
    )
    {
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(CourseRepository $rep, Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $page = $request->query->getInt('page', 1);
            $boolQuery = new BoolQuery();

            if ($data->query) {
                $boolQuery->addMust(new MatchPhrase('title', $data->query));
            }

            if ($data->category) {
                $boolQuery->addFilter(new MatchQuery('category.id', $data->category->getId()));
            }

            if ($data->createdThisMonth) {
                $range = new Range('createdAt', [
                    'gte' => (new \DateTimeImmutable('-1 month'))->format('Y-m-d'),
                ]);

                $boolQuery->addFilter($range);
            }

            $query = new Query($boolQuery);
            $query->setHighlight(
                [
                    'fields' => [
                        'title' => [
                            'pre_tags' => ['<strong style="color:rosybrown">'], // Highlight start tag
                            'post_tags' => ['</strong>'], // Highlight end tag
                        ],
                        'category' => [
                            'matched_fields' => ['category.title'],
                            'pre_tags' => ['<strong style="color:rosybrown">'], // Highlight start tag
                            'post_tags' => ['</strong>'], // Highlight end tag
                        ]
                    ]
                ]
            );



            $results = $this->finder->createHybridPaginatorAdapter($query);
            $pagination = $this->paginator->paginate($results, $page);


            // Process results to include highlights
            $highlightedResults = [];

            foreach ($pagination->getItems() as $item) {
                $highlightedTitle =  $item->getResult()->getHit()['highlight']['title'][0] ?? $item->getTransformed()->getTitle();
                $highlightedResults[] = [
                    'titleModif' => $highlightedTitle,
                    'title' => $item->getTransformed()->getTitle() ?? null,
                    'category' =>  $item->getTransformed()->getCategory() ?? null,
                    'createdAt' => $item->getTransformed()->getCreatedAt() ?? null,
                ];
            }


        }

        return $this->render('search/index.html.twig',
            [
                'form' => $form->createView(),
                'pagination' => $highlightedResults ?? [],
                'paginate' => $pagination
            ]
        );
    }
}
