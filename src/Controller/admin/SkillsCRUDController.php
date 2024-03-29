<?php

namespace App\Controller\admin;

use App\Entity\Skill;
use App\Form\Type\SkillType;
use App\Helper\Helper;
use App\Repository\SkillRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SkillsCRUDController extends AbstractController
{
    /**
     * @var string
     */
    private string $currentPage;

    /**
     * @param SkillRepository $skillRepository
     * @param RequestStack $requestStack
     */
    public function __construct(private SkillRepository $skillRepository, private RequestStack $requestStack)
    {
        $this->currentPage = Helper::getPageName($this->requestStack->getCurrentRequest()->getPathInfo());
    }

    /**
     * @return Response
     */
    #[Route('/admin/skills', name: 'app_admin_skills', methods: 'GET')]
    public function index(): Response
    {
        $skills = $this->skillRepository->findAll();

        return $this->render('admin/skills/show.html.twig', [
            'skills' => $skills,
            'currentPage' => $this->currentPage
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/admin/skills/create', name: 'app_admin_skills_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $skill = new Skill();
        $form  = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        return $this->renderForm('admin/skills/create.html.twig', [
            'form' => $form,
            'currentPage' => $this->currentPage,
            'skill' => $skill
        ]);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     */
    #[Route('/admin/skills/update/{id}', name: 'app_admin_skills_update', methods: ['GET', 'POST'])]
    public function update(int $id, Request $request): Response
    {
        $skill = $this->skillRepository->findOneBy(compact('id'));
        $form  = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $skill = $form->getData();
            $this->skillRepository->add($skill, flush: true);
            $this->addFlash('success', "La compétence <strong>{$skill->getName()}</strong> a bien été éditée");

            return $this->redirectToRoute('app_admin_skills');
        }

        return $this->render('admin/skills/update.html.twig', [
            'skill' => $skill,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route('/admin/skills/delete/{id}', name: 'app_admin_skills_delete', options: ['expose' => true], methods: 'GET')]
    public function delete(int $id): Response
    {
        $skill = $this->skillRepository->findOneBy(compact('id'));
        $file  = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skill->getImage();

        $this->skillRepository->remove($skill, flush: true);
        if (file_exists($file)) {
            unlink($file);
        }

        $this->addFlash('success', "La compétence <strong>{$skill->getName()}</strong> a bien été supprimée");
        return $this->redirectToRoute('app_admin_skills');
    }

    /**
     * @param int $id
     * @param int $order
     * @return Response
     */
    #[Route('/admin/skills/update/{id}/displayOrder/{order}', name: 'app_admin_skills_update_display_order', methods: ['GET', 'POST'])]
    public function updateDisplayOrder(int $id, int $order): Response
    {
        $skill = $this->skillRepository->findOneBy(compact('id'));
        $skill->setDisplayOrder($order);
        $this->skillRepository->add($skill, flush: true);

        return $this->redirectToRoute('app_admin_skills');
    }


    /**
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    #[Route('/admin/skills/image', name: 'app_admin_skills_image', methods: ['GET', 'POST'])]
    public function getImage(Request $request, FileUploader $fileUploader): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundException();
        }

        $skill = new Skill();
        $form  = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        /** @var $file */
        $file = $_FILES['file'];

        if($form->isSubmitted() && $form->isValid())
        {
            # this condition is mandatory since the image field is not required
            $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type']);

            $skillImageName = $fileUploader->upload($file);
            $skill->setImage($skillImageName);
            $this->skillRepository->add($skill, flush: true);
        }

        return new Response('done');
    }
}
