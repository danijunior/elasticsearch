<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CourseController extends AbstractController
{
    #[Route('/course/{title}', name: 'course_show')]
    public function index(string $title, CourseRepository $courseRepository): Response
    {
        return $this->render('course/index.html.twig', [
            'course' => $courseRepository->findOneByTitle($title)
        ]);
    }
}
