<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\Type\ContactType;
use App\Repository\ProjectRepository;
use App\Repository\SkillRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\Email;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @param SkillRepository $skillRepository
     * @param ProjectRepository $projectRepository
     * @param Request $request
     * @param Email $email
     * @return Response
     */
    #[Route('/', name: 'app_home')]
    public function index(SkillRepository $skillRepository, ProjectRepository $projectRepository, Request $request, Email $email): Response
    {
        # SKILLS SECTION
        $skills = $this->getSkills($skillRepository);

        # PORTFOLIO SECTION
        $projects = $this->getProjects($projectRepository);
        
        # CONTACT SECTION
        $form = $this->showContactForm($request, $email);

        return $this->render('frontend/index.html.twig', [
            'skills' => $skills,
            'projects' => $projects,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param $skillRepository
     * @return mixed
     */
    private function getSkills($skillRepository): mixed
    {
        return $skillRepository->findAll();
    }

    /**
     * @param $projectRepository
     * @return mixed
     */
    private function getProjects($projectRepository): mixed
    {
        return $projectRepository->findAll();
    }

    /**
     * @param $request
     * @param $email
     * @return FormInterface
     */
    private function showContactForm($request, $email): FormInterface
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $email->send($formData, '/frontend/emails/email_contact.mjml.twig');

            $this->addFlash('success', 'Votre email a bien été envoyé');
            $this->redirectToRoute('app_home');
        }

        return $form;
    }
}
